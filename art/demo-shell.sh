# Shell setup for art/demo.tape (sourced while the recording is hidden).
# The A2A CLI prints plain text; this only adds color to its output. The
# words are exactly what the CLI prints.

export PS1='\[\e[1;32m\]my-agent\[\e[0m\] \[\e[1;34m\]$\[\e[0m\] '

# A caption for the next step, on a clean screen.
step() { clear; printf '\e[1;33m▸ %s\e[0m\n\n' "$1"; }

a2a() {
    command a2a "$@" | tee /tmp/a2a-demo-last.txt | sed -u \
        -e 's/^\(Name: *\)\(.*\)/\1\x1b[1;36m\2\x1b[0m/' \
        -e 's/^\(Task\|Context\|Status\|History\|Interfaces\|Skills\|Streaming\|Description\|Version\|Name\|Resume\|Artifacts\):/\x1b[2m\1:\x1b[0m/' \
        -e 's/\(working\)/\x1b[33m\1\x1b[0m/' \
        -e 's/\(completed\)/\x1b[1;32m\1\x1b[0m/' \
        -e 's/\(input-required\)/\x1b[1;35m\1\x1b[0m/' \
        -e 's/\(submitted\)/\x1b[34m\1\x1b[0m/' \
        -e 's/^\(\[artifact\] \)\(.*\)/\1\x1b[1;32m\2\x1b[0m/' \
        -e 's/^\(  \[conversion\] \)\(.*\)/\1\x1b[1;32m\2\x1b[0m/' \
        -e 's/\(To which unit?.*\)/\x1b[1;36m\1\x1b[0m/' \
        -e 's/\(\[user\]\|\[agent\]\)/\x1b[2m\1\x1b[0m/' \
        -e 's/\(\[status\]\|\[artifact\]\)/\x1b[2m\1\x1b[0m/'
    # The last task's id, so the demo can answer it with --task-id "$TASK".
    TASK=$(awk '/^Task:/ {print $2; exit}' /tmp/a2a-demo-last.txt)
}
