#!/bin/bash

data=`cat realtime.csv`
IFS=';' read -ra fields <<< "$data"

dcCount=2
acCount=3

#for i in "${fields[@]}"; do
#	echo "$i"
#done

function calc {
	echo "$1"|bc -l
}

function round {
	val=`calc "$1*1000000"`
	echo "scale=6
	      `LC_ALL=C printf %.0f "$val"`/1000000"|bc -l
}

function parse {
	len=${#fields[@]}
	date=`date --date=@${fields[0]} "+%d-%m-%Y %T"`
	vv1=${fields[1]}
	va1=${fields[1+dcCount+acCount]}
	vv2=${fields[2]}
	va2=${fields[2+dcCount+acCount]}
	vw=${fields[len-3]}
	cv1=`calc "$vv1 / (65535 / 1600)"`    # Volt DC
	ca1=`calc "$va1 / (65535 / 200)"`     # Amper DC
	cv2=`calc "$vv2 / (65535 / 1600)"`    # Volt DC
	ca2=`calc "$va2 / (65535 / 200)"`     # Amper DC
	cw=`calc "$vw / (65535 / 100000)"`    # Watt AC

	rwdc1=`calc "$cv1 * $ca1"`
	rwdc2=`calc "$cv2 * $ca2"`

	rcv1=`round $cv1`
	rca1=`round $ca1`
	rcv2=`round $cv2`
	rca2=`round $ca2`
	rdc1=`round $rwdc1`
	rdc2=`round $rwdc2`
	rac=`round $cw`
	echo "$date;$rcv1;$rca1;$rcv2;$rca2;$rdc1;$rdc2;$rac"
}

parse