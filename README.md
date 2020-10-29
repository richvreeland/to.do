# two.dew
a task list for people who like to be both lazy and organized

Welcome to my blurry, dumb task app that doesn't care about days.

## Set Up ##
First things first : this to do app uses PHP and writes txt files, so it needs a server.

In order to create tasks and start using this thing, you have to make some categories. Create a file called `categories.txt`. Here is an example of how it should be formatted:

```
🦚️, #000000, 50, Miscellaneous
🌊️, #8ADCE3, 55, Project Great Wave
🕳️, #FFAF00, 80, Diaper Golf
🐚️, #489B84, 80, Conch Congas
🚆️, #D35D65, 51, Train Drain
🚘️, #2C313E, 54, Cardar, cd car
🌟️, #945AA4, 75, Nord Star
💧️, #146EF0, 99, two.dew, dew
```

Each row has 4 values, with an optional 5th :
emoji, hex color code, priority, category name, custom search shorthand

The emoji and hex color code are for visual purposes, while the priority number will sort projects by their importance within each time period.

The optional 5th will allow you to short circuit the task entry system to use the keyword of your choice, which is explained in more detail below.

## Tasks ##
Tasks are timestamped and automatically move up over time.

- You can create tasks using shorthand. Try entering this up top: 'Ride the great wave. \pgw \y'
- You can use acronym shorthand for task categories, and time periods.
- Shorthand is always initialized with a back slash.
- Shorthand for time periods: 0 (this week), 1 (next week), 2 (two weeks), 3 or m (next month), 4 or q (next quarter), 5 or h (next half), 6 or y (next year).
- Shorthand for categories uses acronyms. So 'Project Great Wave' is \pgw. You can add special search terms for categories in categories.txt, which is where categories are customized. This is especially useful if you have categories with similar acronyms. It happens!

## Shortcuts ##
- 'f' to show/hide future events. '/' to start typing a new task!

## etc ##
- if you ever make a profound mistake, there is a cache of deleted and edited tasks, so fret not! currently lives in a txt file, but perhaps can make this into a 'recover' feature at some point.
