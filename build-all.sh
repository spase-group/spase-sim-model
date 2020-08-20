#!/bin/bash
# Build all files for all versions of the informaiton model.

node ../../spase-model-tools-node/src/makejson.js -c ../overview.json -r 1.0.0 -o model/spase-sim-1.0.0.json spase-sim-1.0.0
