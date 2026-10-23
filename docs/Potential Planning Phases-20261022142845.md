# Potential Planning Phases

### Phase 1: Architecture & Tenancy Strategy

Before writing code, you must decide how to separate the baseball organizations.

**The Strategy:** **Single Database, Multi-Tenant (Logic-based)**.
Do not create a separate database for every organization. It is overkill. Instead, add an `organization_id` column to almost every table.

**The "Golden Rule" Trait:**
Create a trait in Laravel called `BelongsToOrganization`.

- It automatically adds a Global Scope: `static::addGlobalScope('org', function ($q) { $q->where('organization_id', session('current_org_id')); })`.
- This ensures that when "Org A" logs in, they physically _cannot_ query "Org B's" players, even by accident.

---

### Phase 2: Database Schema (The Backbone)

You need four main "clusters" of data.

### 1\. The SaaS Layer (You getting paid)

- **`users`**: The logins (Admins, Coaches, Parents).
- **`organizations`**: The entity buying your software (e.g., "Cary Trojans Baseball").
- **`organization_user`**: Pivot table (User ID 1 is an "Admin" for Org ID 5).
- **`subscriptions`** / **`subscription_items`**: (Standard Laravel Cashier tables).

### 2\. The Organizational Layer

- **`seasons`**: (e.g., "Spring 2025", "Fall 2025"). Critical for filtering data year-over-year.
- **`divisions`**: (e.g., "10U", "11U", "Varsity").
- **`teams`**: (e.g., "10U Red", "10U Blue"). Belongs to a Division and a Season.

### 3\. The Dynamic Form Layer (Your Form Builder)

- **`forms`**: The container (Name: "2025 Tryout Registration", Schema: `JSON` of fields).
- **`submissions`**: The answers (User ID, Form ID, Data: `JSON` answers).

### 4\. The Roster Layer

- **`players`**: The actual kid (Name, DOB, Jersey Size).
- **`guardians`**: The parents (links to `users`).
- **`team_player`**: Pivot table. **Crucial:** This needs a `season_id` column. A player is on "10U Red" in 2024, but "11U Blue" in 2025.

---

### Phase 3: The Development Roadmap

Here is the sequence I would build this in to minimize headaches.

### Step 1: The SaaS Onboarding (Laravel Cashier)

Don't build the baseball tools yet. Build the gate.

1. Install **Laravel Cashier (Stripe)**.
2. Create a Registration flow:
    - User signs up -> Creates an Organization Name.
    - Redirect to Stripe Checkout.
    - On success -> Redirect to Dashboard.
    - **Middleware:** Create a middleware `CheckSubscription` that blocks access if their Stripe status is not `active`.

### Step 2: The "Org" Settings

Build the CRUD for the setup data. An Org admin needs to set these up before they can accept players.

- Create Seasons.
- Create Divisions (Age groups).
- Invite Assistant Admins.

### Step 3: The Form Builder (The Livewire Logic)

This is the "Form Creator" we discussed.

1. **Builder UI:** Admin drags/drops fields to create a "Registration Questionnaire."
2. **Public View:** Create a public-facing Livewire component (e.g., `app.com/org/cary-trojans/register`) where parents fill this out.
3. **Payment:** Integrate Stripe _again_ here. The Org pays _you_ (Subscription), but the Parents pay the _Org_ (Registration Fees). You will likely need **Stripe Connect** for this so the money goes to the Org, not you.

### Step 4: Player Management (The Data Pool)

When a parent submits a form:

1. Create a `Submission` record.
2. Automatically create/update a `Player` record.
3. This creates the "Unassigned Player Pool."

### Step 5: The Team Manager (The Core Utility)

This is where your app provides value.

1. **Team Creation:** Admin creates "12U Red".
2. **Rostering:** A Dual-Listbox UI (Left side: Unassigned Players, Right side: Team Roster).
    - _Livewire Feature:_ Select 5 players on the left, click "Move >", update the database.
3. **Exporting:** Generate PDF rosters or CSV exports for tournaments.

---

### Phase 4: Specific Tech Implementation Tips

**1\. Managing "Active" Organization**
Since a user (like a coach) might belong to two different baseball orgs, store their current context in the Session.

PHP

`// In a middleware or login listener`
`session(['current_org_id' => $user->latest_organization_id]);`

**2\. Subscription Logic**
Use Laravel Cashier.

- **Plan A (Per Team):** Charge $10/month per team.
- **Plan B (Flat Rate):** Charge $99/month per Org.
- _Recommendation:_ Start with **Flat Rate**. It is much easier to code. You don't have to listen for "TeamCreated" events to update Stripe quantity.

**3\. The "Parent" User Problem**

- **Don't** force parents to create an account just to register (initially). It kills conversion rates.
- **Do** let them register as a "Guest" first, then offer them to "Claim Account" later to manage their kid's profile.

### Summary Checklist for You

- [ ] **Install:** Laravel, Livewire, Tailwind, Laravel Cashier.
- [ ] **Models:** Create `Organization`, `Team`, `Player` with the `organization_id` column.
- [ ] **Stripe:** Set up your Product in Stripe Dashboard (e.g., "Monthly Subscription - $49").
- [ ] **Livewire Component 1:** `OrgSignup` (Input Org Name -> Stripe Checkout).
- [ ] **Livewire Component 2:** `FormBuilder` (The JSON schema creator).
