<?php
/*
* Structure of DATA
*
*  teachers
*    0                          // first teacher of this school
*      id: int
*      full_name: string
*    1                          // second teacher of this school
*      ...
*  student_limits
*    min: int
*    max: int
*  school_name: string
*  tabs
*    0                          // first tab containing "first" segment
*      id: string  // the id of the segment
*      segment_label: string
*      col_left:                // left column
*        0                      // first group of left column
*          id: int
*          name: string
*          teacher_id: int
*          nr_students: int
*          food: string
*          info: string
*          visits
*            0                  // first visit of first group of left column
*              id: int
*              date: string
*              topic_short_name: string
*              topic_url: string
*              confirmed: boolean
*              show_confirm_link: boolean
*            1                  // second visit of first group of left column
*              ...
*        1                      // second group of left column
*          ...
*      col_right:               // right column
*        ...
*    1                          // second tab containing "second" segment
*      ...
*
* */
