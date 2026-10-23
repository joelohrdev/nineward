# Initial Research

Here is a checklist of the specific constraints, columns, and relationships you need to account for to avoid painting yourself into a corner later.

1. The SaaS & Tenancy Layer

• organization\_id Everywhere: almost every single table (except users, subscriptions, and system tables) must have an organization\_id.
◦ Why: To apply the Global Scope where('organization\_id', $currentOrg).
• User Multi-Tenancy: A single User (email) must be able to belong to multiple Organizations.
◦ Schema: Do not put organization\_id on the users table. Use a pivot table: organization\_user (with a role column like 'admin', 'coach').
• Subscription Status: The organizations table needs columns for Stripe status (handled by Laravel Cashier), but you also need a trial\_ends\_at column if you plan to offer free trials without a card.

1. The "Global" vs. "Seasonal" Split

You must decide which data persists forever and which dies when a season ends.
• Global Entities (No season\_id):
◦ players: A kid is the same kid forever.
◦ divisions: "10U" is always "10U".
◦ fields/locations: The baseball field address doesn't change per season.
• Seasonal Entities (Must have season\_id):
◦ teams: "2024 10U Red" is different from "2025 11U Red".
◦ roster\_entries (Pivot): The link between a global player and a seasonal team.
◦ games/events: Happen at a specific point in time within a season.

1. The Player & Family Identity

• Guardians vs. Users:
◦ Not every parent will register. You need a guardians table that stores contact info (phone/email) without requiring a users table login.
◦ Relationship: players has many guardians.
• The "Unclaimed" Profile:
◦ You will have players created by Admins (manual entry) and players created by Parents (registration forms).
◦ Account For: A user\_id column on the guardians table that is nullable. If it's null, they are an "unclaimed" contact. If it has an ID, they can log in.

1. The Form Builder (JSON vs. Relational)

• Schema Storage:
◦ forms table needs a schema column (JSON/LongText) to store the layout (fields, labels, types).
• Submission Storage:
◦ submissions table needs a data column (JSON) for the answers.
• The "Mapped" Columns:
◦ Critical: You cannot easily search JSON. If you need to filter by "Birth Date" or "Jersey Size", you should duplicate those specific fields into actual columns on the players table when the form is processed, rather than only keeping them in the JSON submission.

1. Financials (If handling payments)

• The Two Payment Types:
◦ Type A (Org pays You): Handled by subscriptions table (Cashier).
◦ Type B (Parent pays Org): If you process registration fees, you need a payments or invoices table linked to the submission\_id or player\_id.
• Refunds/Status: Account for status columns (pending, paid, failed, refunded).

1. Roster Logic

• Jersey Numbers:
◦ These are Seasonal. Do not put jersey\_number on the players table.
◦ Where it goes: It belongs on the team\_player pivot table, because the kid might be #23 in Spring but #5 in Fall.
• Primary Positions:
◦ Likely belongs on the players table (Global preference) OR the pivot table (Role on this specific team).

1. Soft Deletes & Archiving

• Soft Deletes: Enable SoftDeletes on players and teams. In youth sports, "deleting" a player usually ends up being a mistake that an admin wants to undo 10 minutes later.
• Slug/URL Safety: If you have public pages (e.g., [myapp.com/org/teams/10u-red](http://myapp.com/org/teams/10u-red)), ensure your slug column is unique per organization, not globally unique.

Summary Schema Visualization
TableScopeKey ColumnsorganizationsGlobalid, name, subdomain, stripe\_idusersGlobalid, email, passwordorganization\_userPivotuser\_id, org\_id, role (admin/coach)seasonsTenantorg\_id, name (Spring 2025), is\_activeplayersTenantorg\_id, first, last, dob (No Jersey # here!)teamsSeasonalorg\_id, season\_id, division\_id, nameteam\_playerSeasonalteam\_id, player\_id, jersey\_numberformsTenantorg\_id, title, schema (JSON)submissionsTenantform\_id, player\_id (nullable), data (JSON)
