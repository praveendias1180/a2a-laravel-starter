# Records art/demo.gif (the README demo) with VHS: https://github.com/charmbracelet/vhs
# Needs the A2A CLI (https://github.com/a2aproject/a2a-cli) on PATH and the app on :8000:
#   php artisan serve &
#   vhs art/demo.tape   (on Ubuntu 24.04+: VHS_NO_SANDBOX=true vhs art/demo.tape)

Output art/demo.gif

Set Shell "bash"
Set FontSize 20
Set Width 1440
Set Height 640
Set Padding 28
Set LineHeight 1.25
Set Theme "Catppuccin Mocha"
Set TypingSpeed 40ms
Set WindowBar Colorful
Set WindowBarSize 36

Hide
Type "source art/demo-shell.sh && step 'A Laravel app that is an A2A agent. What can it do?'"
Enter
Show

Sleep 600ms
Type "a2a card get localhost:8000"
Sleep 300ms
Enter
Sleep 4.5s

Hide
Type "step 'Ask it something, and stream the answer'"
Enter
Show
Sleep 600ms
Type 'a2a send -a localhost:8000 --stream "convert 10 km to miles"'
Sleep 300ms
Enter
Sleep 5s

Hide
Type "step 'Leave out the unit, and the agent asks for it'"
Enter
Show
Sleep 600ms
Type 'a2a send -a localhost:8000 "convert 10 km"'
Sleep 300ms
Enter
Sleep 5s

Hide
Type "step 'Answer on the same task, and it finishes the job'"
Enter
Show
Sleep 600ms
Type 'a2a send -a localhost:8000 --task-id $TASK "miles"'
Sleep 300ms
Enter
Sleep 6s
