#!/bin/bash

ffmpeg -i $1/$2 -acodec mp3 -ab 128k $1/$3.mp3