# My Tree Enviros Backend — Slider API

This document describes the public Slider endpoints that power the homepage hero carousel in the frontend.

These endpoints are public (no authentication required) and return signed media URLs for images. Signed URLs expire — see “Image delivery and caching” below for details.


## Base URL

- Local: http://localhost:8000
- Prod/Staging: https://your-domain.example

All endpoints below are prefixed with `/api`:
- List sliders: GET /api/sliders
- Show slider: GET /api/sliders/{id}


## Endpoints

### GET /api/sliders

Returns a list of sliders. By default only active sliders are returned.

Query parameters:
- active (boolean, optional): Filter by active status. Defaults to true when omitted.
- limit (integer, optional): Limit number of results (1–100).
- order (string, optional): Sort order by ID; one of asc, desc. Default: desc.

Example cURL:
```sh
curl -sS "${API_URL:-http://localhost:8000}/api/sliders"
```

With parameters:
```sh
curl -sS \
  "${API_URL:-http://localhost:8000}/api/sliders?active=true&limit=5&order=desc"
```

Example 200 response:
```json
{
  "data": [
    {
      "id": 7,
      "title": "Plant More Trees",
      "description": "Join our green mission and plant a tree today.",
      "is_active": true,
      "main_image_url": "http://localhost:8000/media/12345?expires=1730000000&signature=abc123",
      "created_at": "2025-10-22T13:25:00.000000Z",
      "updated_at": "2025-10-22T13:25:00.000000Z"
    }
  ]
}
```


### GET /api/sliders/{id}

Returns a single slider by ID.

Path parameters:
- id (integer, required): Slider ID.

Example cURL:
```sh
curl -sS "${API_URL:-http://localhost:8000}/api/sliders/7"
```

Example 200 response:
```json
{
  "data": {
    "id": 7,
    "title": "Plant More Trees",
    "description": "Join our green mission and plant a tree today.",
    "is_active": true,
    "main_image_url": "http://localhost:8000/media/12345?expires=1730000000&signature=abc123",
    "created_at": "2025-10-22T13:25:00.000000Z",
    "updated_at": "2025-10-22T13:25:00.000000Z"
  }
}
```

Example 404 response:
```json
{
  "message": "No query results for model [App\\Models\\Slider] 999"
}
```


## Schema

Slider (resource: App\Http\Resources\Api\V1\SliderResource)
- id (integer)
- title (string|null)
- description (string|null)
- is_active (boolean)
- main_image_url (string|null): Temporarily signed URL to the slider image (see media notes below)
- created_at (string|null, ISO8601)
- updated_at (string|null, ISO8601)

OpenAPI Annotations:
- Resource: components.schemas.Slider
- Endpoints: GET /api/sliders, GET /api/sliders/{id}

You can regenerate the OpenAPI spec and browse it (see “OpenAPI docs” below).


## Image delivery and caching

- The API returns a signed URL in `main_image_url`. It is generated against the `media.show` route, which streams media files securely.
- Signed URLs currently expire after 60 minutes.
- Frontends must be ready to refetch the slider list occasionally (e.g., on page load) to get fresh `main_image_url` values.
- If using Next.js Image Optimization, configure `images.remotePatterns` (or `domains`) to allow the backend host so that Next/Image can fetch these URLs.

Example Next.js config snippet:
```js
// next.config.js / next.config.ts
export default {
  images: {
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8000',
        pathname: '/media/**',
      },
      {
        protocol: 'https',
        hostname: 'your-domain.example',
        pathname: '/media/**',
      },
    ],
  },
}
```


## Notes for integrators

- Active-only default: If you want to preview inactive sliders in an admin UI or staging frontend, pass `?active=false`.
- Limit: Use `?limit=5` (or similar) for homepages to reduce payload.
- Ordering: The default `desc` returns most recent first (by ID).
- Backend storage: The model stores a single file in the `image` media collection. If older records used `images`, the resource falls back to that.


## OpenAPI docs

This project ships with Swagger (l5-swagger).
- Generate docs:
  ```sh
  composer docs
  ```
- The UI is typically available at:
  - http://localhost:8000/api/documentation

If the UI route is different in your environment, check your Swagger config (config/l5-swagger.php).


## Versioning

These endpoints are part of the API v1 namespace under `/api`. Backwards-incompatible changes will be introduced in a new versioned path (e.g., `/api/v2`) when necessary.