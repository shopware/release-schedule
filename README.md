## Shopware Release Schedule

This repository provides an up-to-date calendar and structured information about Shopware’s product release schedules, including automated generation and publishing of SVG assets that visualize the release lifecycle. The schedule and SVG graphics help the Shopware community, partners, and users track upcoming releases, understand version timelines, and plan upgrades.

## What’s Included?

- **Release Timetable**:  
  - Versioned product releases for Shopware (minor and major versions)
  - Support and End-of-Life (EOL) dates
  - Major milestones such as feature freeze and release candidates

- **SVG Generation & Publishing**:  
  - Automated scripts generate SVG diagrams visualizing the current release schedule (e.g., version timelines with active/support phases).
  - The generated SVG files are published directly to the repository for easy linking in documentation, websites, or dashboards.

- **Structured Data**:  
  - Human-readable tables (Markdown, HTML)
  - Machine-readable formats for tool integration

## SVG Generation and Publishing Workflow

1. **SVG Generation**  
   - The repository contains a script that processes the latest release data (typically from a YAML, JSON, or Markdown source file).
   - The script transforms this structured data into an SVG diagram illustrating the release schedule.

2. **Automated Publishing**  
   - After generation, the SVG file (e.g., `schedule.svg`) is placed in the repository — typically in the root or a dedicated assets directory (e.g., `/assets/`).
   - In most setups, SVG generation is triggered automatically via a CI workflow (e.g., GitHub Actions) whenever the schedule data is updated.
   - With each change, the freshly generated SVG is committed and pushed alongside the updated schedule data, ensuring users always have access to the current visual representation.

3. **Usage**  
   - The published SVG can be embedded in documentation, linked from the main README, or displayed on external sites via its raw GitHub URL.
   - Example inclusion in Markdown:
     ```
     ![Shopware Release Schedule](./schedule.svg to Use

- Browse the schedule in Markdown or JSON to keep track of planned and historical releases.
- View or embed the SVG to display a visual representation of the Shopware release lifecycle.
- Integrate the structured data or SVG in your processes, dashboards, or customer documentation.

## Example

| Version   | Release Date | End of Life | Notes                  |
|-----------|-------------|-------------|------------------------|
| 6.6.0     | 2024-06-07  | 2025-12-07  | Latest stable version  |
| 6.5.0     | 2023-05-15  | 2024-12-01  | Previous stable        |
| ...       | ...         | ...         | ...                    |

View the latest visual in [`schedule.svg`](./schedule.svg).


## Contributing

If you notice outdated or incorrect dates, please open an issue or submit a pull request. Updates to the release schedule will automatically trigger SVG regeneration and publishing.
