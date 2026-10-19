-- One fleet per user creation. A vehicle (plate number) can belong to several fleets,
-- each fleet keeping its own known location of the vehicle.
-- Column sizes follow the limits of the domain (UserId::MAX_LENGTH, PlateNumber::MAX_LENGTH).

CREATE TABLE fleets (
    id      UUID PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL
);

CREATE TABLE fleet_vehicles (
    fleet_id     UUID NOT NULL REFERENCES fleets (id) ON DELETE CASCADE,
    plate_number VARCHAR(32) NOT NULL,
    latitude     DOUBLE PRECISION NULL, -- NULL: the vehicle has never been parked
    longitude    DOUBLE PRECISION NULL,
    altitude     DOUBLE PRECISION NULL,
    PRIMARY KEY (fleet_id, plate_number),
    CHECK ((latitude IS NULL) = (longitude IS NULL)),
    CHECK (altitude IS NULL OR latitude IS NOT NULL)
);
