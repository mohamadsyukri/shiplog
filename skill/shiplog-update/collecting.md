# Collecting the records

Paths in the config may start with `~`; expand them. Every day is a date in the config's
`timezone`, never in UTC.

## Git commits

For each lane, for each folder in `repos` (skip and report a folder that doesn't exist):

```bash
TZ=Asia/Kuala_Lumpur git -C ~/code/client-portal log --all --no-merges \
  --author='you@example.com' --author='you@work.example' \
  --since='2026-10-19' --until='2026-10-22' \
  --date=format-local:%Y-%m-%d --pretty=format:'%ad%x09%h%x09%s'
```

- Repeat `--author` once per address in `git_emails`; git matches any of them.
- `TZ` plus `--date=format-local` prints each commit's author date as a day in that timezone.
- `--since` and `--until` look at the committer date, which a rebase moves, so ask for a day more
  on each side and keep only the lines whose printed day is in your range.
- Each line is `day`, short sha, subject: the sha is the source's `ref`, the subject its `label`.

## Claude Code sessions

Claude Code keeps one transcript per session in `~/.claude/projects/<folder>/<session-id>.jsonl`,
where `<folder>` is the session's working directory with every `/` and `.` turned into `-`:
`/home/me/code/client-portal` becomes `-home-me-code-client-portal`, and `MyApp2.0` becomes
`MyApp2-0`.

- **Lane:** turn each lane's `repos` folders into folder names the same way. A transcript folder
  that equals one, or starts with one followed by `-` (a subfolder), belongs to that lane. When
  several match, because one lane's repo sits inside another lane's folder (`~/work` in one lane,
  `~/work/app` in another), the longest match wins. Any other folder goes to `catch_all_lane`.
- **Lines:** each line is a JSON object. Read those whose `type` is `user` or `assistant`. Their
  `timestamp` is UTC (`...Z`); convert it to `timezone` for the day. `message.content` is a string
  or a list of blocks; read the `text` blocks and skip `tool_use` and `tool_result`.
- A session that runs past midnight counts on each day it has messages.
- A subfolder named after a session holds its subagents' transcripts: part of the same session.
- Claude Code deletes transcripts after `cleanupPeriodDays` (30 by default), so older days have
  commits only. Report that gap.

Transcripts reach several megabytes, so read them with a short script instead of opening them.
Save it outside every repository (a temp folder) and run it with `python3 -I`:

```python
# python3 -I sessions.py <transcript folder> <timezone> <from> <to>
# Prints one line per message: day, session id, who, the first 300 characters of its text.
import json, pathlib, sys
from datetime import datetime
from zoneinfo import ZoneInfo

folder, zone, first, last = pathlib.Path(sys.argv[1]).expanduser(), ZoneInfo(sys.argv[2]), sys.argv[3], sys.argv[4]
for path in sorted(folder.glob('*.jsonl')):
    for line in path.open(encoding='utf-8'):
        try:
            row = json.loads(line)
        except json.JSONDecodeError:
            continue
        if row.get('type') not in ('user', 'assistant') or 'timestamp' not in row:
            continue
        day = datetime.fromisoformat(row['timestamp'].replace('Z', '+00:00')).astimezone(zone).date().isoformat()
        if not first <= day <= last:
            continue
        content = (row.get('message') or {}).get('content')
        blocks = [content] if isinstance(content, str) else [b.get('text', '') for b in content or [] if isinstance(b, dict) and b.get('type') == 'text']
        # Collapse line breaks and tabs so each message stays on one line.
        text = ' '.join(' '.join(t for t in blocks if t).split())
        if text:
            print(f"{day}\t{path.stem}\t{row['type']}\t{text[:300]}")
```

Read the output for what was asked and what got done. It can contain secrets: they inform your
summary, never its words.

## Artifacts

Only when the Artifact tool is available: list the user's own artifacts (`action: list`,
`scope: mine`, `limit: 200`). Each row has a title, a link and when it was last updated. An
artifact last updated on a day in your range is a source in `catch_all_lane` that day: `ref` is the
id at the end of its link, `label` its title, `url` its link. Only the last update is known, so
earlier days of work on it don't show.
