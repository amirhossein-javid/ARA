# ARA Studio Website

This project is a static, Persian, right-to-left MVP for ARA Studio. It requires no framework, database, or build process and can be deployed directly to shared hosting or cPanel.

## Project Structure

- `index.html` — Main landing page and all content sections
- `assets/styles.css` — Responsive layout and visual identity
- `assets/app.js` — Mobile navigation, data-driven portfolio rendering, motion, parallax, and form behavior
- `assets/portfolio-data.js` — Maintainable portfolio content, screenshot paths, Telegram URLs, and status values
- `assets/portfolio/` — Six clearly labeled temporary sample previews
- `assets/ara-logo.png` — Provided ARA logo
- `assets/fonts/Vazirmatn-wght.woff2` — Locally hosted Vazirmatn variable webfont
- `assets/fonts/OFL.txt` — SIL Open Font License for Vazirmatn

## Deploying to cPanel

1. Upload the project contents to `public_html` or the selected domain directory.
2. Make sure `index.html` is placed directly in the domain's root directory.
3. To enable real form submissions, replace the current behavior in `assets/app.js` with an approved email, WhatsApp, or API integration.
4. When real portfolio material becomes available, place screenshots in `assets/portfolio/` and update each item in `assets/portfolio-data.js`.
5. Add only verified `https://t.me/...` URLs to `telegramUrl`; the CTA stays non-clickable while the value is empty.

## Content Note

The current portfolio entries are clearly identified as temporary design samples and do not claim to represent real clients or completed commercial projects. There are no category filters, live demos, or project detail pages. The website contains no fabricated prices, statistics, testimonials, client logos, Telegram URLs, or contact details.
