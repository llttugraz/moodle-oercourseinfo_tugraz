# moodle-oercourseinfo_tugraz

Subplugin for [local_oer](https://github.com/llttugraz/moodle-local_oer) plugin

# Requirements

* Moodle 3.9+
* local_coursesync - not yet publicly accessible
* coursesync_lectures - not yet publicly accessible
* [local_oer](https://github.com/llttugraz/moodle-local_oer)

# Content

As there are dependencies that are not publicly accesible yet, this source code is intended as example, how to implement this subplugin type for the [local_oer](https://github.com/llttugraz/moodle-local_oer) plugin  

In the TeachCenter (the Moodle instance used by Graz University of Technology) Moodle courses can be linked to several external courses from TUGRAZonline (CAMPUSonline instance - the campus management software). This is used to synchronise groups, teachers and students to the moodle courses.  

This subplugin gathers the information about the linked courses and adds them to the metadata information for the [local_oer](https://github.com/llttugraz/moodle-local_oer). The metadata about the courses is added to the file metadata.  
