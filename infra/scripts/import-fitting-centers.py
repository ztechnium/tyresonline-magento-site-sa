#!/usr/bin/env python3
"""
Import KSA fitting centers from Excel into ecomteck_storelocator_stores.

Creates English (store_id=1) and Arabic (store_id=2) rows per location.

Usage:
  python import-fitting-centers.py "Fitting Centers.xlsx" --sql-only -o import.sql
  python import-fitting-centers.py "Fitting Centers.xlsx" --execute \
      --host HOST --user magento --password PASS --database tyresonline_sa
"""
from __future__ import annotations

import argparse
import json
import re
import sys
from datetime import datetime, time
from pathlib import Path

try:
    import pandas as pd
except ImportError:
    print("Install pandas and openpyxl: pip install pandas openpyxl", file=sys.stderr)
    raise

COUNTRY = "SA"
DEFAULT_CATEGORY_IDS = "1945"
STORE_EN = "1"
STORE_AR = "2"


def slugify(value: str) -> str:
    value = value.lower().strip()
    value = re.sub(r"[^a-z0-9]+", "-", value)
    return value.strip("-") or "fitting-center"


def clean_str(value) -> str:
    if value is None or (isinstance(value, float) and pd.isna(value)):
        return ""
    text = str(value).strip()
    return "" if text.lower() == "nan" else text


def parse_money(value) -> str | None:
    if value is None or (isinstance(value, float) and pd.isna(value)):
        return None
    text = str(value).strip()
    if not text:
        return None
    match = re.search(r"([\d.]+)", text.replace(",", ""))
    return match.group(1) if match else None


def parse_time(value) -> time | None:
    if value is None or (isinstance(value, float) and pd.isna(value)):
        return None
    if isinstance(value, time):
        return value
    if isinstance(value, datetime):
        return value.time()
    text = str(value).strip()
    for fmt in ("%H:%M:%S", "%H:%M"):
        try:
            return datetime.strptime(text, fmt).time()
        except ValueError:
            continue
    return None


def fmt_hhmm(t: time) -> str:
    return t.strftime("%H:%M")


def build_day_slots(open_t: time, close_t: time, step_minutes: int = 120) -> list[list[str]]:
    slots: list[list[str]] = []
    start = datetime.combine(datetime.today(), open_t)
    end = datetime.combine(datetime.today(), close_t)
    if end <= start:
        return slots
    cursor = start
    while cursor < end:
        nxt = cursor + pd.Timedelta(minutes=step_minutes)
        if nxt > end:
            nxt = end
        if nxt > cursor:
            slots.append([fmt_hhmm(cursor.time()), fmt_hhmm(nxt.time())])
        cursor = nxt
    return slots


def build_opening_hours(open_value, close_value) -> str:
    open_t = parse_time(open_value)
    close_t = parse_time(close_value)
    if not open_t or not close_t:
        day = json.dumps([], separators=(",", ":"))
        return json.dumps([day] * 7, separators=(",", ":"))
    day_slots = json.dumps(build_day_slots(open_t, close_t), separators=(",", ":"))
    days = [json.dumps([], separators=(",", ":"))] + [day_slots] * 6
    return json.dumps(days, separators=(",", ":"))


def sql_escape(value) -> str:
    if value is None:
        return "NULL"
    return "'" + str(value).replace("\\", "\\\\").replace("'", "''") + "'"


def build_intro(fitting_cost, runflat_cost, grade) -> str | None:
    intro_parts = []
    if fitting_cost:
        intro_parts.append(f"Fitting: {fitting_cost} SAR")
    if runflat_cost:
        intro_parts.append(f"Runflat: {runflat_cost} SAR")
    if grade:
        intro_parts.append(f"Grade: {grade}")
    return " | ".join(intro_parts)[:255] if intro_parts else None


def build_intro_ar(fitting_cost, runflat_cost, grade) -> str | None:
    intro_parts = []
    if fitting_cost:
        intro_parts.append(f"التركيب: {fitting_cost} ريال")
    if runflat_cost:
        intro_parts.append(f"رانفلات: {runflat_cost} ريال")
    if grade:
        intro_parts.append(f"التصنيف: {grade}")
    return " | ".join(intro_parts)[:255] if intro_parts else None


def row_to_records(row: pd.Series) -> list[dict]:
    branch_code = clean_str(row.get("Branch_Code"))
    name_en = clean_str(row.get("Branch Name EG"))
    name_ar = clean_str(row.get("Branch Name AR"))
    city_en = clean_str(row.get("City English"))
    city_ar = clean_str(row.get("City Arabic"))
    address_en = clean_str(row.get("Adress_Eng"))
    address_ar = clean_str(row.get("Adress_Arb"))
    lat = clean_str(row.get("Latitude"))
    lng = clean_str(row.get("Longtitude"))
    map_link = clean_str(row.get("Google Map Link"))
    supervisor = clean_str(row.get("Center Supv. Name"))
    phone = re.sub(r"[^\d+]", "", clean_str(row.get("Center Supv. No.")))
    email = clean_str(row.get("Brnach Email"))
    services = clean_str(row.get("Services"))
    toll_free = clean_str(row.get("Toll-Free Number"))
    fitting_cost = parse_money(row.get("Fitting cost"))
    runflat_cost = parse_money(row.get("Runflat "))
    grade = clean_str(row.get("Grade"))

    open_t = parse_time(row.get("open_time"))
    close_t = parse_time(row.get("close_time"))

    hours_text_en = ""
    hours_text_ar = ""
    if open_t and close_t:
        hours_text_en = f"Daily: {fmt_hhmm(open_t)} - {fmt_hhmm(close_t)}"
        hours_text_ar = f"يومياً: {fmt_hhmm(open_t)} - {fmt_hhmm(close_t)}"

    shared = {
        "country": COUNTRY,
        "postcode": None,
        "region": None,
        "email": email or None,
        "phone": phone or toll_free or None,
        "image": None,
        "latitude": lat,
        "longitude": lng,
        "status": 1,
        "station": branch_code[:255] if branch_code else None,
        "external_link": map_link[:255] if map_link else None,
        "opening_hours": build_opening_hours(row.get("open_time"), row.get("close_time")),
        "category": DEFAULT_CATEGORY_IDS,
        "is_all_products": 0,
        "ismobilevan": "0",
        "shipping_amount": fitting_cost,
        "pickup_service": "1",
        "whatsapp_number": phone[:255] if phone else None,
        "skip_days": "0",
        "closed_days": "[]",
    }

    desc_en_parts = [p for p in [services, supervisor] if p]
    if toll_free:
        desc_en_parts.append(f"Toll-free: {toll_free}")

    desc_ar_parts = [p for p in [name_ar, services, supervisor] if p]
    if toll_free:
        desc_ar_parts.append(f"الرقم المجاني: {toll_free}")

    en = {
        **shared,
        "store_id": STORE_EN,
        "name": name_en,
        "address": address_en[:255],
        "city": city_en[:255],
        "url_key": slugify(f"{branch_code}-{name_en}")[:255],
        "description": " | ".join(desc_en_parts)[:255] if desc_en_parts else None,
        "intro": build_intro(fitting_cost, runflat_cost, grade),
        "opening_hours_text1": hours_text_en[:255] if hours_text_en else None,
        "opening_hours_text2": address_ar[:255] if address_ar else None,
    }

    ar_address = address_ar
    if city_ar and city_ar not in address_ar:
        ar_address = f"{address_ar}, {city_ar}" if address_ar else city_ar

    ar = {
        **shared,
        "store_id": STORE_AR,
        "name": name_ar[:255] if name_ar else name_en,
        "address": ar_address[:255] if ar_address else address_en[:255],
        "city": city_ar[:255] if city_ar else city_en[:255],
        "url_key": slugify(f"{branch_code}-ar-{name_en}")[:255],
        "description": " | ".join(desc_ar_parts)[:255] if desc_ar_parts else None,
        "intro": build_intro_ar(fitting_cost, runflat_cost, grade),
        "opening_hours_text1": hours_text_ar[:255] if hours_text_ar else hours_text_en[:255],
        "opening_hours_text2": address_ar[:255] if address_ar else None,
    }

    return [en, ar]


def generate_sql(records: list[dict]) -> str:
    lines = [
        "-- KSA fitting centers import (UTF-8)",
        f"-- Generated {datetime.utcnow().isoformat()}Z",
        "SET NAMES utf8mb4;",
        "START TRANSACTION;",
        "DELETE FROM ecomteck_storelocator_stores;",
        "ALTER TABLE ecomteck_storelocator_stores AUTO_INCREMENT = 1;",
        "",
    ]
    columns = [
        "store_id", "name", "address", "city", "country", "postcode", "region",
        "email", "phone", "url_key", "image", "latitude", "longitude", "status",
        "station", "description", "intro", "external_link", "opening_hours",
        "category", "is_all_products", "ismobilevan", "shipping_amount",
        "pickup_service", "opening_hours_text1", "opening_hours_text2",
        "whatsapp_number", "skip_days", "closed_days", "created_at", "updated_at",
    ]
    now = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")
    for rec in records:
        values = []
        for col in columns:
            if col in ("created_at", "updated_at"):
                values.append(sql_escape(now))
            elif col == "is_all_products":
                values.append(str(rec.get(col, 0)))
            elif col == "status":
                values.append(str(rec.get(col, 1)))
            elif col == "pickup_service":
                values.append(sql_escape(rec.get(col, "1")))
            else:
                values.append(sql_escape(rec.get(col)))
        lines.append(
            "INSERT INTO ecomteck_storelocator_stores ("
            + ", ".join(columns)
            + ") VALUES (" + ", ".join(values) + ");"
        )
    lines.extend(["COMMIT;", ""])
    return "\n".join(lines)


def main() -> int:
    parser = argparse.ArgumentParser(description="Import KSA fitting centers from Excel")
    parser.add_argument("excel", type=Path, help="Path to Fitting Centers.xlsx")
    parser.add_argument("--sql-only", action="store_true", help="Write SQL file only")
    parser.add_argument("-o", "--output", type=Path, help="SQL output path (default: stdout)")
    parser.add_argument("--execute", action="store_true", help="Run SQL against MySQL")
    parser.add_argument("--host", default="tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com")
    parser.add_argument("--user", default="magento")
    parser.add_argument("--password", required=False)
    parser.add_argument("--database", default="tyresonline_sa")
    parser.add_argument("--category-ids", default=DEFAULT_CATEGORY_IDS)
    args = parser.parse_args()

    df = pd.read_excel(args.excel)
    df = df.dropna(how="all")
    records: list[dict] = []
    for _, row in df.iterrows():
        for rec in row_to_records(row):
            rec["category"] = args.category_ids
            records.append(rec)

    sql = generate_sql(records)

    if args.sql_only or args.output:
        out_path = args.output or Path("import-fitting-centers.sql")
        out_path.write_text(sql, encoding="utf-8")
        print(f"Wrote {len(records)} rows ({len(df)} locations x EN+AR) to {out_path}", file=sys.stderr)
        if not args.execute:
            return 0

    if args.execute:
        if not args.password:
            print("--execute requires --password", file=sys.stderr)
            return 1
        try:
            import mysql.connector  # type: ignore
        except ImportError:
            print("Install mysql-connector-python for --execute", file=sys.stderr)
            return 1

        conn = mysql.connector.connect(
            host=args.host,
            user=args.user,
            password=args.password,
            database=args.database,
            charset="utf8mb4",
            use_unicode=True,
        )
        try:
            cur = conn.cursor()
            for statement in sql.split(";\n"):
                statement = statement.strip()
                if statement and not statement.startswith("--"):
                    cur.execute(statement)
            conn.commit()
        finally:
            conn.close()
        print(f"Imported {len(records)} fitting center rows.", file=sys.stderr)
        return 0

    if not args.sql_only:
        sys.stdout.reconfigure(encoding="utf-8")
        print(sql)
        print(f"-- Preview: {len(records)} rows", file=sys.stderr)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
