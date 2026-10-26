---
name: shiplog-update
description: Use when the user asks to update their Shiplog board or work diary ("update my board", "fill in my diary", "what did I do this week" for Shiplog), or to backfill Shiplog from their git commits and Claude Code sessions for a range of days.
---

# Updating a Shiplog board

## Overview

Shiplog shows one summary card per lane per day. This skill turns the user's own records (git
commits, Claude Code sessions, artifacts) into a `shiplog/v1` file, has the app check it, and
imports it. **A card says only what the records show.**

## Before you start

Read `~/.config/shiplog/config.json`. Its shape is `config.example.json`, next to this file. If it
is missing, stop and ask the user to create it from the example; never guess lanes, emails or
paths.

## The update

1. **Pick the days.** When the user names them ("the last two days", "September"), use exactly
   those. Otherwise find the newest `shiplog-<from>_to_<to>.json` in `output_dir` and start on its
   `<to>` day: that day is done again on purpose, so work finished late still gets in, and
   re-importing a day only replaces its cards. With no file yet, the start is the earliest record
   (the first commit by `git_emails` in any lane's `repos`, or the oldest transcript): tell the user
   how many months that covers and wait for their go before starting. End today, in `timezone`.
2. **Long ranges: one file per calendar month.** Months don't depend on each other. When you can
   start subagents, give each month to one, with this skill's path and the month's first and last
   day, and let each do steps 3 to 5.
3. **Gather records per day.** Commands and formats are in `collecting.md`:
   - commits by any of `git_emails`, on all branches, in each lane's `repos`;
   - Claude Code sessions, laned by their project folder; a session outside every lane's `repos`
     goes to `catch_all_lane`;
   - artifacts updated that day, only when the Artifact tool is available, in `catch_all_lane`.

   A lane with no record on a day gets no card.
4. **Write the cards,** one per lane per day:
   - `summary`: 2 to 4 plain sentences on what got done, built from the records, at most 600
     characters;
   - `sources`: every record behind it (see the table below);
   - `lanes`: every lane from the config, with `key`, `name`, `color` and `order`.
5. **Write, check, import.** Save `output_dir/shiplog-<from>_to_<to>.json`. Run `import.command`
   (with `{name}` replaced by the file name) plus ` --dry-run`, fix every problem it lists, and
   check again until it passes. Then, for `import.mode` `command`, run it without `--dry-run`; for
   `upload`, give the user the file's path to upload on the Imports screen.
6. **Report** the days covered, the cards per lane, and every gap: a repo folder that doesn't exist
   (skip it), days before the oldest transcript (commits only), no Artifact tool.

## Secrets never go into a file

Summaries and labels describe the work. They never contain passwords, tokens, keys, connection
strings, `.env` values or personal data seen in the records, and never paste a prompt, a diff or
tool output. Write "rotated the API key", not the key.

## Quick reference

| Source | `ref` | `label` | `url` |
|---|---|---|---|
| `commit` | short sha | the commit subject | null |
| `session` | the session id (transcript file name) | a short title you write | null |
| `artifact` | the artifact id | its title | its link |
| `note` | a stable name | a short title | an http(s) link or null |

The full format, with an example, is in the Shiplog README under "Import file format".

## Common mistakes

| Mistake | Instead |
|---|---|
| Grouping by the UTC date | Convert every timestamp to `timezone` first; 01:00 local is that day, not yesterday. |
| Counting colleagues' or merge commits | Only `git_emails`, with `--no-merges`. |
| One card per commit or per session | One card per lane per day; commits and sessions become its sources. |
| "Worked on the project" | Name the outcome: "Fixed the invoice total on split orders". |
| Importing before `--dry-run` passes | The check refuses the whole file; fix and re-check first. |
