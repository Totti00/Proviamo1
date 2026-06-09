# Real Estate Brokerage WordPress Implementation

This repository now includes a production-ready WordPress plugin at:

`/tmp/workspace/Totti00/Proviamo1/wp-content/plugins/reb-real-estate`

## Implementation path chosen
- **Approach:** Custom build using a dedicated plugin (custom post type + taxonomies + custom metadata).
- **Why:** Gives simple admin UX, role-based ownership controls, and no lock-in to a heavy theme framework.
- **Map provider:** Leaflet + OpenStreetMap (Google Maps can be swapped in later if you want API-key-based maps).

## Delivered features

### 1) Listing management for team
- Property custom post type.
- Taxonomies: `property_type`, `property_location`.
- Property details meta box:
  - Price
  - Status (`available` / `sold`)
  - Address
  - Latitude/Longitude
  - Features
  - Gallery image IDs + media selector button
- Admin list columns show status + price.
- Sold listings can be hidden by default from search/archive and included with a checkbox filter.

### 2) Front-end listing experience
- Archive/search UI with filters:
  - Price min/max
  - Property type
  - Location
  - Include sold toggle
- Responsive listing cards.
- Single property page with:
  - Gallery
  - Map pin (interactive)
  - Features list
  - Contact agent form

### 3) Agent and client accounts
- **Agent role** can manage only their own property records.
- **Client role** can register with shortcode and save favorites.
- Favorites are client-only and handled via secure AJAX.
- Contact form submits inquiries to the listing agent email.

### 4) SEO/performance baseline
- JSON-LD property schema output on single listing pages.
- Lazy-loaded listing/gallery images.
- Clean CPT archive URL structure (`/properties`).
- Mobile-first responsive CSS.

## Short hand-off guide (for non-technical staff)

### Install
1. Copy folder `reb-real-estate` into `/wp-content/plugins/` on your WordPress site.
2. In WordPress Admin → Plugins, activate **REB Real Estate Brokerage**.
3. Go to Settings → Permalinks and click **Save** once.

### Create listing
1. Go to **Properties → Add New**.
2. Enter title, description, featured image.
3. Fill **Property Details** box:
   - Price
   - Status (Available/Sold)
   - Address + coordinates
   - Features
   - Gallery images (use button)
4. Assign Property Type and Location.
5. Publish.

### Edit listing
1. Go to **Properties → All Properties**.
2. Open listing, update fields/images, click **Update**.

### Mark sold
1. Edit listing.
2. Set **Status = Sold**.
3. Update post.

### Remove/archive listing
- To remove permanently: move listing to Trash.
- To archive without deleting: keep listing but set status to Sold and leave “Include sold” unchecked in public search.

### Client accounts + favorites
- Add `[reb_client_register]` to a page to allow client registration.
- Logged-in clients can click **Save to favorites** on listing pages.

### Search page embed
- Add `[reb_property_search]` to any page for property search/filter UI.

## Optional plugin stack recommendations
- SEO: Yoast SEO or Rank Math
- Caching/performance: WP Rocket or LiteSpeed Cache
- Image optimization: ShortPixel or Imagify
- Security: Wordfence
- Backups: UpdraftPlus
- Analytics: Site Kit by Google

## Browser support target
- Latest Chrome, Edge, Firefox, Safari.

## Notes for future enhancements
- Add Google Maps provider with API key settings panel.
- Add saved-search alerts via email.
- Add advanced availability/workflow states.
- Add video walkthrough recording for your internal team if desired.
