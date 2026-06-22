#!/bin/bash
grep -i 'entityId' /tmp/analysis.html | head -30
echo '---CHANNEL LINES---'
grep -i 'channel' /tmp/analysis.html | head -25
echo '---8ac IDs---'
grep -oE '8ac[0-9a-f]{30}' /tmp/analysis.html | sort -u
echo '---select options---'
grep -i 'option value' /tmp/analysis.html | head -40
