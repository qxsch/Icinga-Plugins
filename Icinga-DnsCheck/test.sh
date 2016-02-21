#!/bin/sh
S=${IFS}
IFS=.
for P in $1; do
  TLD=${P}
done
IFS=${S}

echo "TLD: ${TLD}"
DNSLIST=$(dig +short ${TLD}. NS)
for DNS in ${DNSLIST}; do
  echo "Checking ${DNS}"
  dig +norec +nocomments +noquestion +nostats +nocmd @${DNS} $1 NS
done

