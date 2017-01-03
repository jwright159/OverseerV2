#!/bin/bash
curl "https://codeload.github.com/uWebSockets/uWebSockets/tar.gz/v0.12.0" | gunzip -f | tar xvf -
cd uWebSockets-0.12.0/
make
