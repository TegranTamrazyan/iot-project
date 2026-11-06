"""Small, beginner-friendly functions for the MySQL database."""

import threading

import mysql.connector

from config import DB_CONFIG

# Prevent two controller threads from running database operations at once.
_db_lock = threading.Lock()


def connect_database():
    """Open a new connection to MySQL/MariaDB."""
    return mysql.connector.connect(**DB_CONFIG)


def run_query(sql, values=(), fetch_one=False, commit=False):
    """Run one SQL statement and always close its cursor and connection."""
    with _db_lock:
        connection = connect_database()
        try:
            cursor = connection.cursor()
            try:
                cursor.execute(sql, values)
                result = cursor.fetchone() if fetch_one else None
                if commit:
                    connection.commit()
                return result
            finally:
                cursor.close()
        finally:
            connection.close()


def save_reading(fridge, temperature, humidity):
    """Save one sensor reading in sensor_readings."""
    run_query(
        """
        INSERT INTO sensor_readings (fridge, temperature, humidity)
        VALUES (%s, %s, %s)
        """,
        (fridge, temperature, humidity),
        commit=True,
    )


def get_threshold(fridge):
    """Return the maximum allowed temperature for one fridge."""
    row = run_query(
        "SELECT max_temperature FROM thresholds WHERE fridge = %s",
        (fridge,),
        fetch_one=True,
    )

    if row is None:
        raise RuntimeError(
            f"No threshold configured for {fridge}. Check data/fridge_iot_schema.sql."
        )

    return float(row[0])


def get_fan_state():
    """Read the current fan state set by the website or controller."""
    row = run_query(
        "SELECT status FROM fan_state WHERE id = 1",
        fetch_one=True,
    )
    return str(row[0]).upper() if row else None


def save_fan_state(status):
    """Save ON/OFF, creating the single fan row if it is missing."""
    run_query(
        """
        INSERT INTO fan_state (id, status)
        VALUES (1, %s)
        ON DUPLICATE KEY UPDATE status = VALUES(status)
        """,
        (status,),
        commit=True,
    )
