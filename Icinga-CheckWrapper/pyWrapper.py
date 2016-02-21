#!/usr/bin/python
import sys
import re
import subprocess


if len(sys.argv)<2:
    print("UNKNOWN - PyWrapper - No command specified")
    sys.exit(3)

try:
    p = subprocess.Popen(sys.argv[1:], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    out, err = p.communicate()
except Exception as e:
    print("UNKNOWN - PyWrapper - Execution failed - " + str(e))
    sys.exit(3)
    

mo = re.search(r"<icingaoutput>(.*)</icingaoutput>", out)
if mo:
    print(mo.group(1).strip())
    mo2 = re.search(r"<icingareturncode>([0123])</icingareturncode>", out)
    if mo2:
        sys.exit(int(mo2.group(1)))
    else:
        sys.exit(p.returncode)
else:
    print("UNKNOWN - PyWrapper - Output not found - please use the tags")
    sys.exit(3)


