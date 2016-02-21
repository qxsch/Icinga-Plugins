#!/bin/bash

echo "error" 1>&2
echo "hi"

if [[ -n "$1" ]]; then
  echo "<icingaoutput>hello wold - $1</icingaoutput>"
fi
if [[ -n "$2" ]]; then
  echo "<icingareturncode>$2</icingareturncode>"
fi

echo "error" 1>&2
echo "hi"
echo "error" 1>&2

exit 2
