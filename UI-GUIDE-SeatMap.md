# Seat map contract

This document describes the JSON and form contract shared by `SeatSelectionController`, `resources/js/seat-map.js`, and `resources/views/booking/seats.blade.php`.

## Map endpoint

`GET /booking/seats/map/{leg}` returns one seat map for the selected journey leg:

```json
{
  "rows": [
    {
      "row": 1,
      "seats": [
        {
          "flight_seat_id": 101,
          "seat_number": "1A",
          "seat_class": "economy",
          "position": "window",
          "col": "A",
          "aisle_after": false,
          "status": "available",
          "selectable": true,
          "fee": 75000
        }
      ]
    }
  ]
}
```

- `flight_seat_id` is the ID submitted to the server; `seat_number` is display text.
- `status` is the effective status. An expired `held` seat is reported as `available`.
- `selectable` is true only when the seat matches the selected fare class and is effectively available.
- `fee` is the `fare_classes.seat_selection_fee` for this booking. The client reads this field to show the estimated optional seat-selection fee. The server recalculates the final price while creating tickets.
- `col` and `aisle_after` define the visual grid; `position` is used for accessible labels.

## Page data and form submission

The Blade page provides JSON datasets for `passengers`, `mapUrls`, and `oldSeats`. Passenger entries use `{ group, index, name, type_label }`, where `group` is `adults` or `children`. Each map URL is keyed by zero-based journey leg.

Selected seats are submitted as nested inputs:

```text
seats[leg][adults][passengerIndex] = flight_seat_id
seats[leg][children][passengerIndex] = flight_seat_id
```

No input is submitted for a passenger who should receive an automatically assigned seat. The server validates the nested input, then checks flight, fare class, availability, and duplicate selections inside the booking transaction.
