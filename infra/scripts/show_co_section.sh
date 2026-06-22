#!/bin/bash
grep -n 'main\|content\|checkout' /tmp/co_full.html | sed -n '900,1150p'
sed -n '980,1060p' /tmp/co_full.html
