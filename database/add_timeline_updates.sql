-- Add timeline_updates column
ALTER TABLE articles ADD COLUMN IF NOT EXISTS timeline_updates JSON NULL AFTER is_breaking;

-- Add sample timeline data for live articles
UPDATE articles 
SET timeline_updates = '[
    {"time": "2024-12-04 14:30:00", "update": "Match started with great enthusiasm from both teams"},
    {"time": "2024-12-04 14:45:00", "update": "First goal scored! Team A takes the lead", "image": null},
    {"time": "2024-12-04 15:00:00", "update": "Half time. Score: 1-0"},
    {"time": "2024-12-04 15:15:00", "update": "Second half begins"},
    {"time": "2024-12-04 15:30:00", "update": "Team B equalizes! Score: 1-1"}
]' 
WHERE is_live = 1 
LIMIT 3;
