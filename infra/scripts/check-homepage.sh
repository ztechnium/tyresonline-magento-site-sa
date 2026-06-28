#!/bin/bash
HTML=$(curl -s https://stg.tyresonline.sa/en/)
echo "=== title ==="
echo "$HTML" | grep -o '<title>[^<]*</title>' | head -1
echo "=== body class ==="
echo "$HTML" | grep -o 'body[^>]*class="[^"]*"' | head -1
echo "=== logo ==="
echo "$HTML" | grep -oi 'luma\|TyresOnline' | sort | uniq -c
echo "=== cms-home ==="
echo "$HTML" | grep -c 'cms-home'
echo "=== pagebuilder ==="
echo "$HTML" | grep -c 'data-content-type'
echo "=== main column sample ==="
echo "$HTML" | grep -o 'column main[^<]*' | head -1
echo "=== compare sidebar in main? ==="
echo "$HTML" | grep -c 'Compare Now'
