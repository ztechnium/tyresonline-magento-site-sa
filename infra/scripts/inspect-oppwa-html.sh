#!/bin/bash
sed -n '1,120p' /tmp/start.html
echo '---ALL PRC LINKS---'
grep -oE '/bip/[a-zA-Z0-9_./-]+\.prc' /tmp/start.html | sort -u
echo '---ALL TEXT---'
sed 's/<[^>]*>/\n/g' /tmp/start.html | grep -v '^[[:space:]]*$' | head -80
