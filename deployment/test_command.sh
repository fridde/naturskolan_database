cd /mnt/d/Dropbox/scripts/testing/naturskolan_database
A=": && "$(echo "./vendor/bin/codecept run acceptance "{Index,SchoolPage,Admin,Cron,Table,Permission}"Cest --steps -f &&")" :" && eval echo $A
