# Season Archiving

In a modern SaaS application, "Archiving" rarely means moving data to a separate "cold storage" database. Instead, it means **scoping your queries**.

You never want to delete the 2024 season data. You just want to **hide** it from the default view when the 2025 season starts.

Here is the strategy for handling Season Archiving and Rollovers in Laravel.

---

### 1\. The Database Strategy: "Logical" Archiving

Do not move data. Instead, anchor everything to a `season_id`.

**The Rule:**

- **Players** are Global (A kid is the same kid in 2024 and 2025).
- **Teams** are Seasonal (The "10U Red" team in 2024 is a different database entity than "11U Red" in 2025).
- **Rosters** are Seasonal (The link between a Player and a Team).

**The Schema:**

PHP

\`// seasons table
$table->id();
$table->foreignId('organization\_id');
$table->string('name'); // e.g., "Spring 2025"
$table->boolean('is\_active')->default(false); // Only one season is active at a time
$table->date('start\_date');
$table->date('end\_date');

// teams table
$table->id();
$table->foreignId('organization\_id');
$table->foreignId('season\_id'); // <--- THE KEY
$table->string('name'); // "12U Red"

// team\_player (Pivot)
$table->id();
$table->foreignId('team\_id');
$table->foreignId('player\_id');
// No season\_id needed here, because team\_id already belongs to a season\`

---

### 2\. The User Experience: The "Context Switcher"

You need a way for the Admin to tell the app: _"I am currently working inside Spring 2025."_

**The Session Strategy:**
When a user logs in, you shouldn't just check what their Org is; you should check what their **Active Season** is.

1. **On Login:** Find the Org's `is_active` season. Store that `season_id` in the User's Session.
2. **The Navbar:** Put a dropdown in your top header: `Current Season: Spring 2025 ▼`.
3. **The Switch:** If they select "Fall 2024" from the dropdown, update the Session variable and refresh the page.

**The Global Scope (or Trait):**
Now, in your Livewire components, you don't need to manually filter every query.

PHP

`// In your Team Model`
`protected static function booted()`
`{`
`static::addGlobalScope('current_season', function (Builder $builder) {`
`// Only apply this if we are in a web request and a season is set`
`if (session()->has('current_season_id')) {`
`$builder->where('season_id', session('current_season_id'));`
`}`
`});`
`}`

_Result:_ When you run `Team::all()`, Laravel automatically adds `WHERE season_id = 5`. The old teams are effectively "Archived" because they simply don't show up.

---

### 3\. The "Rollover" (Starting a New Season)

When an Org starts a new season, they don't want to re-type 20 team names. You need a **Clone/Import Utility**.

Create a Livewire component called `SeasonRollover`.

**The Workflow:**

1. **Create:** Admin clicks "Start New Season".
2. **Input:** Asks for Name ("Spring 2026").
3. **Wizard:** Asks "Do you want to import structure from a previous season?"
4. **Execution:**
    - It creates the new Season record.
    - It loops through the old season's _Teams_.
    - It creates _new_ Team records attached to the _new_ Season ID.
    - _Crucial:_ It does **NOT** copy the players (Rosters). Rosters usually start empty in a new season because kids move up or quit.

**The Code Logic:**

PHP

\`public function rollover($oldSeasonId, $newSeasonName)
{
DB::transaction(function () use ($oldSeasonId, $newSeasonName) {
// 1. Create New Season
$newSeason = Season::create(\[
'organization\_id' => auth()->user()->organization\_id,
'name' => $newSeasonName,
'is\_active' => true
\]);

```php
    // 2. Deactivate Old Season
    Season::where('id', $oldSeasonId)->update(['is_active' => false]);

    // 3. Clone Teams (Optional Step)
    $oldTeams = Team::where('season_id', $oldSeasonId)->get();

    foreach($oldTeams as $team) {
        Team::create([
            'organization_id' => $team->organization_id,
            'season_id' => $newSeason->id, // Link to NEW season
            'name' => $team->name,
            'division_id' => $team->division_id, // Assuming divisions are global?
        ]);
    }
});

// 4. Update User Session
session(['current_season_id' => $newSeason->id]);

return redirect()->to('/dashboard');
```

}\`

### 4\. Handling "Global" vs "Seasonal" Players

This is the biggest mistake devs make in sports apps.

- **Scenario:** Johnny plays 10U in 2024. Johnny plays 11U in 2025.
- **The Player Table:** Johnny has **one** record in the `players` table. Do not duplicate Johnny.
- **The View:**
    - When viewing the "2024" season, Johnny appears on the 10U roster.
    - When viewing the "2025" season, Johnny appears on the 11U roster.
    - When viewing the "All Players" Master List (Admin only), you ignore the Season Scope, so you can see every kid who has ever played for the org.

### Summary of Tasks

1. **Migration:** Add `season_id` to `teams` and `matches` tables.
2. **Model:** Create the `Season` model with an `is_active` boolean.
3. **Middleware/Service:** Create the logic to store `current_season_id` in the session.
4. **UI:** Build the Dropdown in the Navbar to switch seasons.
5. **Livewire:** Build the "Rollover Wizard" to clone teams to the new season ID
