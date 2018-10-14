#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
for i in `seq 1 11`;
	do php $DIR/../index.php > $DIR/../index.html;
	sleep 5;
done
