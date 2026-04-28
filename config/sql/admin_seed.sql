USE AutoSpares;


-- Email: admin@autospares.local
-- Password: Admin123!
INSERT INTO Users (
    FirstName,
    LastName,
    Role,
    Email,
    PhoneNumber,
    Address,
    City,
    Country,
    Password,
    CreatedAt,
    UpdatedAt
)
VALUES (
    'System',
    'Admin',
    'Admin',
    'admin@autospares.local',
    '0700000000',
    'Workshop HQ',
    'Harare',
    'Zimbabwe',
    '$2y$10$DVBAObxKitkhvSDvgumzMukjVNNmlr.jbdIKDIJKJfiuNFr7YgeCq',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    Role = VALUES(Role),
    FirstName = VALUES(FirstName),
    LastName = VALUES(LastName),
    PhoneNumber = VALUES(PhoneNumber),
    Address = VALUES(Address),
    City = VALUES(City),
    Country = VALUES(Country),
    Password = VALUES(Password),
    UpdatedAt = NOW();


