#!/usr/bin/env bash

A=": && "$(echo "codecept run acceptance "{Index,SchoolPage,Admin,Cron,Table,Permission}"Cest --steps -f &&")" :" && eval echo $A