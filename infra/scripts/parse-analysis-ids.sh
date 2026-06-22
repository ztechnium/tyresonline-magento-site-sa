#!/bin/bash
grep -oE '8ac[0-9a-f]{30}' /tmp/analysis.html | sort -u
echo '---CI OPTIONS---'
grep -oE 'option value="8ac[^"]+" title="[^"]+"' /tmp/analysis.html | head -50
echo '---CHANNEL/FLEX---'
grep -i 'flexChannel\|channelName\|channelId\|paymentBrand\|subType' /tmp/analysis.html | head -30
