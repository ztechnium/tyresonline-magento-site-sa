#!/bin/bash
echo "=== checkout html structure ==="
head -c 2000 /tmp/co_full.html
echo
echo "---"
grep -oE '<body[^>]*>' /tmp/co_full.html | head -1
grep -c '<main' /tmp/co_full.html
grep -c 'checkout' /tmp/co_full.html
grep -c 'requirejs' /tmp/co_full.html
grep -c 'data-bind' /tmp/co_full.html
grep -c 'knockout' /tmp/co_full.html
grep -c 'onepage' /tmp/co_full.html
echo "=== main content snippet ==="
grep -oP '<main[^>]*>.*?</main>' /tmp/co_full.html | head -c 1500
echo
echo "=== column main ==="
grep -oP 'column main[^<]*<[^>]*>.*' /tmp/co_full.html | head -c 1000
