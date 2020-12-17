# Self-assessment question behaviour

This question behaviour allows students to self-assess their own attempt
at a question. At the moment it only works with the Record audio question type,
but this is a limitation that will be removed in future.

## Installation

### Install from the plugins database

Install from the Moodle plugins database https://moodle.org/plugins/qbehaviour_selfassess
in the normal way.

### Install using git

Or you can install using git. Type this commands in the root of your Moodle install

    git clone https://github.com/moodleou/moodle-qbehaviour_selfassess.git question/behaviour/selfassess
    echo /question/behaviour/selfassess/ >> .git/info/exclude

Then run the moodle update process
Site administration > Notifications

### Setup

Once this plugin is installed, if you have a 'Record audio' question in
a quiz (or similar) that is set to use 'Immediate feedback' or
'Interactive with multiple tries', then rather than having to be
manually graded by the teacher, the question becomes self-assessed.

That is, once they have submitted, students can rate their submission on a scale
from one to five stars, with an optional comment.
