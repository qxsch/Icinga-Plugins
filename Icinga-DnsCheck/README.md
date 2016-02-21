# check_dns.py

a simple dns checker

```
Usage: check_dns.py --query=domain.tld [--record=A] [--host=ns.server.tld] --expect=1.2.3.4

    --query=domain.tld    Record name to query (required)
    --record=A            Record type to query (optional)
    --host=ns.server.tld  DNS Server to query  (optional)
    -H ns.server.tld      DNS Server to query  (optional)
    --expect=1.2.3.4      Expected value(s), multiple repetitions allowed (required)
    -v                    Verbose output
    -h  or  --help        Show this help
```
