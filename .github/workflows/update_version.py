import re
from pathlib import Path
import datetime
from github import Github
from github.GithubException import GithubException, UnknownObjectException
import os
import subprocess
import secrets

def check_input(key: str) -> bool:
    """
    Checks if a given key was passed in as an input variable
    """
    return f'{key}' in os.environ and os.environ[f'{key}'] != ""

def run_command(cmd: list[str], end_group: bool = False):
    """
    Runs a given command, surrounding output with ::stop-commands::
    :param cmd: command to run
    :param end_group: whether to run "::endgroup::" before exiting
    """
    token = secrets.token_urlsafe(32)
    print(f"::debug::Running {cmd}")
    proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    out, _ = proc.communicate()
    print(f"::stop-commands::{token}")
    print(out.decode())
    if proc.returncode != 0:
        print(f"::{token}::")
        if end_group:
            print("::endgroup::")
        print(f"::error::❌ Command {cmd} returned with non-zero exit code!")
        exit(proc.returncode)
    print(f"::{token}::")

# Read inputs & put them into variables
if not check_input("GITHUB_TOKEN"):
    print("::error::❌ Missing required input: GITHUB_TOKEN")
    exit(1)
token = os.environ['GITHUB_TOKEN']

if not check_input("RELEASE_TAG"):
    print("::error::❌ Missing required input: RELEASE_TAG")
    exit(1)
tag_name = os.environ['RELEASE_TAG']

if not check_input("PLUGIN"):
    print("::error::❌ Missing required input: PLUGIN")
    exit(1)
plugin = os.environ['PLUGIN']

print(plugin)

# load plugin file
txt = Path(plugin + '.php').read_text()

# get old version
try:
    oldVersion = re.search(r'\* Version:[ \t]*([\d.]+)', txt).group(1)
except Exception as e:
    exit()

# replace with new
txt = txt.replace(oldVersion, tag_name)

# Write changes
f = open(plugin + '.php', "w")
f.write(txt)
f.close()

# Update the changelog with the new release

file    = 'CHANGELOG.md'

# load changelog file
changelog = Path(file).read_text()

# Get the whole unrelease section
try:
    total       = re.search(r'## \[Unreleased\] - yyyy-mm-dd([\s\S]*?)## \[', changelog).group(1)
    newTotal    = total

    # Remove empty sections
    for x in ["Added", "Changed", "Fixed", "Updated"]:
        pattern = r'(### ' + x + r'[\s\S]*'

        if(x != 'Updated'):
            pattern = pattern + '?)###'
        else:
            pattern = pattern + ')'

        added   = re.search(pattern, total).group(1)

        if(added.rstrip("\n") == '### '+x):
            newTotal    = newTotal.replace(added, '')

    # Update in changelog
    changelog   = changelog.replace(total, newTotal)
except Exception as e:
    pass

# Add new unreleased section
newSection  = "## [Unreleased] - yyyy-mm-dd\n\n### Added\n\n### Changed\n\n### Fixed\n\n### Updated\n\n## [" + tag_name + "] - " + datetime.datetime.now().strftime("%Y-%m-%d")+"\n"
changelog    = changelog.replace('## [Unreleased] - yyyy-mm-dd', newSection)

# Write changes
f = open(file, "w")
f.write(changelog)
f.close()

# 
# Create Release
# Copied from https://github.com/mini-bomba/create-github-release
#

# A workaround for the "dubious ownership" error
print('::debug::😩 Attempting a workaround for the "dubious ownership" git error')
run_command(["git", "config", "--global", "--add", "safe.directory", "/github/workspace"])

# Create Github object
github = Github(base_url=os.environ['GITHUB_API_URL'],
                login_or_token=os.environ['GITHUB_TOKEN'],
                user_agent="mini-bomba/create-github-release")

# Get the repo
repo = github.get_repo(os.environ['GITHUB_REPOSITORY'])

# Check current release state
print("👀 Checking current state of the release")
release = None
try:
    release = repo.get_release(tag_name)
except UnknownObjectException:
    release = None

if release is not None:
    print("👌 Release found, copying missing input data")
else:
    print("❗ Release does not exists (yet)")
    if newTotal is None:
        print("::error::Input parameter 'newTotal' must be passed if the release does not exist")
        exit(1)

if release is not None:
    print("📝 Updating data...")
    release.update_release(tag_name, newTotal)
else:
    print("📝 Creating new release...")
    release = repo.create_git_release(tag_name, tag_name, newTotal)
print("::endgroup::")
print("👌😎 Release created!")