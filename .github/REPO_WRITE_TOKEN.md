REPO_WRITE_TOKEN (opt-in autofix / PR updates)

Purpose
-------
A repository secret named `REPO_WRITE_TOKEN` can be set to a short-lived Personal Access Token (PAT) when the repo maintainers want workflows to be able to modify PR bodies or otherwise push changes in cases where `GITHUB_TOKEN` lacks the required permissions (for example, updating PR bodies for PRs originating from forks).

Design & security
-----------------
- Default behaviour: the workflows will NOT use `REPO_WRITE_TOKEN` automatically. PR-body updates are performed using the default `GITHUB_TOKEN` only for same-repo PRs.
- Opt-in: Cross-repo (fork) PRs may be updated only when both:
  - The PR opt-in is explicit (one of: add label `autofix-allow` on the PR, or include the text `[enable-autofix]` in the PR body), and
  - A repository secret `REPO_WRITE_TOKEN` exists (PAT with appropriate minimal scope).
- Token scope guidance: choose the minimal scope needed for the operation. For public repos `public_repo` (or `repo` for private repos) is required for modifying PRs. Prefer a PAT that is dedicated to automation and rotate it regularly.
- Audit trail: workflows write an audit comment when they exercise the PAT so there is a clear record of automated changes.

How to add the secret
---------------------
1. Create a PAT under your GitHub account (Settings -> Developer settings -> Personal access tokens). Choose the minimal scopes (e.g. `repo` for private repositories). Do not reuse a personal token with broad scopes.
2. In the repository navigate to Settings -> Secrets -> Actions -> New repository secret. Name the secret `REPO_WRITE_TOKEN` and paste the PAT.
3. Add the `autofix-allow` label to PRs that should accept cross-repo body updates, or include `[enable-autofix]` in the PR body.

Best practices
--------------
- Rotate PATs regularly and monitor usage.
- Restrict the PAT's permissions and consider using a machine account if available.
- Never commit tokens to source or logs. Workflows look up the token via `secrets.REPO_WRITE_TOKEN`.

Notes
-----
- This repository adds a request/label opt-in flow before using `REPO_WRITE_TOKEN` to avoid silent or unexpected cross-repo updates.
- If you prefer to disable this feature entirely, delete the `REPO_WRITE_TOKEN` secret and the opt-in steps will not run for fork PRs.

Smoke test
----------
- There is a smoke test workflow that runs when the `autofix-allow` label is added to a PR originating from a fork and the `REPO_WRITE_TOKEN` secret exists.
- The smoke test updates the PR body with a short marker and posts an audit comment showing the PAT was exercised. To run the smoke test: add the `autofix-allow` label to the PR.
- The smoke test will not run unless the repo secret `REPO_WRITE_TOKEN` is present and the PR explicitly opts in.
