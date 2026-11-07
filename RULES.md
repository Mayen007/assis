## Rules for agent

- Run against the real system on a fresh, empty database.
- Create all test data through the application screens, not through SQL.
- After each action, check the database with a SQL query.
- Record the real result of every test. A failed test is a valid result.
- Record the environment: operating system, PHP version, MySQL version (SELECT VERSION();), browser, and date.

## Test data

Create these records through the application:

- **Users:** `admin` (Admin), `desk1` (FrontDesk), `tech1` (Technician, Refrigeration), and `tech2` (Technician, Audio Visual). Use passwords of at least 12 characters.
- **Customers:** Grace Wanjiku, `0700111222`; and `<script>alert(1)</script>`, `0700333444`.
- **Products:** Samsung Fridge, model `RT38`, serial `SN-FR-1`, warranty `2026-01-01` to `2028-01-01`; LG TV, model `OLED55`, serial `SN-TV-1`, warranty `2025-01-01` to `2025-06-01`.
- **Spare parts:** Compressor (stock 10, reorder level 5, price 3,500); Thermostat (stock 2, reorder level 5, price 800, then receive 3); TV Panel (stock 1, reorder level 1, price 12,000).
- **Repair:** labour 1,000; use two compressors and one thermostat.

## Test cases

Record the actual result and database check for every case.

| ID  | Test                                                                                                                                | Expected result                                                                                                                   |
| --- | ----------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| T01 | First run, admin setup, repeat setup, and a 5-character password                                                                    | Database is created. Repeat setup is refused. Weak password is refused.                                                           |
| T02 | Submit without a CSRF token; enter SQL injection text in search and ID fields; submit a script tag as a name                        | No record is saved. No SQL error occurs. The script is displayed as text.                                                         |
| T03 | Front desk opens users, reports, inventory, and technicians; technician opens customers, billing, inventory, and reports            | Each unauthorized page redirects.                                                                                                 |
| T04 | Technician opens another technician’s request                                                                                       | Request access is refused by redirect.                                                                                            |
| T05 | Admin deactivates a user with an open session                                                                                       | The open session ends.                                                                                                            |
| T06 | Create a valid customer and product; try a duplicate phone, duplicate serial, and a request for a product owned by another customer | Valid records are saved. Duplicate and mismatched records are refused.                                                            |
| T07 | Submit phone `abc`, e-mail `not-an-email`, warranty end before start, and purchase date `2099`                                      | All invalid values are refused.                                                                                                   |
| T08 | Submit a valid request, an empty description, and an invalid priority                                                               | Valid request is saved as Pending. Invalid requests are refused with a clear message.                                             |
| T09 | Assign the fridge to the AV technician, then to the Refrigeration technician                                                        | First assignment is refused. Second is accepted and status becomes In Progress.                                                   |
| T10 | Assign a fourth active job to one technician                                                                                        | Assignment is refused; the limit is three active jobs.                                                                            |
| T11 | Send three assignments concurrently for one available slot                                                                          | Exactly one assignment succeeds. Use three logged-in sessions and a thread barrier.                                               |
| T12 | Complete a repair using the repair data above                                                                                       | Compressor stock changes from 10 to 8; thermostat stock changes from 5 to 4. Invoice total is 8,800. Request status is Completed. |
| T13 | Request 99 compressors and attempt to use the same part twice beyond available stock                                                | Request is refused. Nothing is saved and stock is unchanged.                                                                      |
| T14 | Have another technician complete the job, then resubmit a completed job                                                             | Both actions are refused. Only one invoice exists.                                                                                |
| T15 | Set an invoice to Paid                                                                                                              | Invoice is Paid and request is Closed.                                                                                            |
| T16 | Submit feedback on a pending request, rating 9, valid feedback, and a duplicate                                                     | Only valid feedback is saved.                                                                                                     |
| T17 | Open the dashboard and every report page as admin                                                                                   | No PHP errors occur.                                                                                                              |
