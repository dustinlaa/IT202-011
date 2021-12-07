CREATE TABLE IF NOT EXISTS Competitions(
    id int AUTO_INCREMENT PRIMARY KEY,
    name varchar(20) not null unique,
    duration int DEFAULT 3,
    expires TIMESTAMP DEFAULT (DATE_ADD(CURRENT_TIMESTAMP, INTERVAL duration DAY)),
    current_reward int DEFAULT (starting_reward),
    starting_reward int DEFAULT 1,
    join_fee int DEFAULT 0,
    current_participants int DEFAULT 0,
    min_participants int DEFAULT 3,
    paid_out tinyint(1) DEFAULT 0,
    min_score int DEFAULT 0,
    first_place_per int default 100,
    second_place_per int default 0,
    third_place_per int default 0,
    cost_to_create int DEFAULT 2,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified timestamp default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    check (first_place_per + second_place_per + third_place_per = 100),
    check (join_fee >= 0),
    check (min_score >= 0),
    check (min_participants >= 3),
    check (starting_reward >= 1),
    check (current_reward >= starting_reward),
    check (current_participants >= 0)
)