# ISDN - IslandLink Sales Distribution Network

World-class e-commerce distribution management system built with PHP, MySQL, and Vanilla JavaScript.

## Quick Start

1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create database: `isdn`
4. Import `DATABASE.sql`
5. Access: http://localhost/ISDN/

## Test Accounts

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| RDC Staff | `rdc_staff` | `rdc123` |
| Customer | `customer` | `customer123` |

## Features

### Authentication & User Management
- Secure login/logout system
- Role-based access control (Admin, RDC Staff, Customer)
- User registration (Customer signup)
- Profile management with image upload
- Password change functionality
- Password visibility toggle
- Session management

### Product Management
- Browse products (all users)
- Admin: Full CRUD operations (Create, Read, Update, Delete)
- Dedicated product edit page
- Image upload with instant preview
- Stock management
- Category filtering
- RDC location filtering
- Search functionality

### Shopping & Orders
- Shopping cart with real-time calculations
- Quantity management
- Checkout process with stock validation
- Order placement and confirmation
- Order history and tracking
- Invoice generation
- Order status management (pending, dispatched, delivered)

### Delivery Management
- Delivery assignment (Admin → RDC Staff)
- Delivery status updates
- GPS tracking with interactive maps (Leaflet.js)
- Delivery timeline visualization
- Real-time tracking API

### Analytics & Reporting
- Basic analytics dashboard
- Advanced analytics with charts (Chart.js)
- Revenue trends (30-day)
- Sales by category
- Top selling products
- CSV export (Orders, Products, Users)
- Currency-aware exports

### Payment System
- Demo credit card payment simulation
- Payment logging
- Payment status tracking

### Design Features
- Beautiful green luxury theme
- Dark/Light mode toggle
- Responsive design (mobile, tablet, desktop)
- Smooth animations and transitions
- Active page highlighting in navigation
- Image preview on upload
- Currency conversion (USD ↔ LKR)

## File Structure

### Core Files
- `db.php` - Database connection and utility functions
- `icons.php` - Icon helper function
- `price-helper.php` - Price formatting
- `send-email.php` - Email notification system

### Components
- `navbar.php` - Dynamic role-based navigation
- `navbar-controls.php` - Currency & theme controls
- `footer.php` - Footer component
- `logout.php` - Logout handler

### Pages
- `index.php` - Homepage
- `login.php` - Login page
- `signup.php` - Customer registration
- `dashboard.php` - Role-based dashboard
- `products.php` - Product catalog
- `product-edit.php` - Product editing (Admin)
- `cart.php` - Shopping cart
- `checkout.php` - Checkout process
- `order-confirmation.php` - Order success page
- `orders.php` - Order management
- `delivery.php` - Delivery management
- `track-order.php` - GPS order tracking
- `users.php` - User management (Admin)
- `profile.php` - User profile
- `analytics.php` - Basic analytics
- `advanced-analytics.php` - Advanced charts
- `generate-invoice.php` - Invoice generation
- `payment.php` - Payment processing
- `export-excel.php` - CSV export

### Styles & Scripts
- `style.css` - Main stylesheet
- `ecommerce-styles.css` - E-commerce specific styles
- `theme-currency.js` - Theme & currency switcher
- `password-toggle.js` - Password visibility toggle
- `image-preview.js` - Image preview functionality

### API & Config
- `api-track.php` - GPS tracking API endpoint
- `.htaccess` - Apache configuration
- `DATABASE.sql` - Database schema

## Technology Stack

- **Backend**: PHP (Procedural)
- **Database**: MySQL (XAMPP)
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Icons**: FontAwesome 6.5.1
- **Maps**: Leaflet.js 1.9.4
- **Charts**: Chart.js 4.4.0
- **No Frameworks**: Pure vanilla implementation

## Configuration

Database settings are in `db.php`:
- Host: `localhost`
- User: `root`
- Password: `` (empty by default)
- Database: `isdn`

Update these values if your XAMPP setup differs.

## System Constants

- **Currency Rate**: 1 USD = 320 LKR
- **Tax Rate**: 5%
- **Shipping**: $3.00 USD
- **Password Hashing**: MD5 (academic use)

## Database Schema

### Tables
- `users` - User accounts (admin, rdc, customer)
- `products` - Product catalog
- `orders` - Customer orders
- `order_items` - Order line items
- `deliveries` - Delivery tracking

### Key Features
- Foreign key constraints
- Unique constraints (username, email)
- Indexed columns for performance
- Timestamp tracking

## Security Features

- SQL injection protection (parameterized queries)
- Session-based authentication
- Role-based access control
- File upload validation
- XSS protection (htmlspecialchars)
- Security headers (.htaccess)

## Notes

- All comments have been removed from source files
- Clean, production-ready codebase
- Email notifications require SMTP configuration (logged to `logs/email.log`)
- Payment system is a demo simulation
- GPS tracking uses simulated coordinates

## Deployment

For free hosting deployment instructions, see `DEPLOYMENT.md`.

Quick steps:
1. Choose free hosting (000webhost, InfinityFree, etc.)
2. Upload all files to `public_html`
3. Create MySQL database in hosting panel
4. Import `DATABASE.sql`
5. Update `db.php` with hosting database credentials
6. Set folder permissions (755 for uploads/logs folders)

See `DEPLOYMENT.md` for detailed instructions.

## License

Academic/Professional project - Use as needed.

---

**Built for IslandLink Sales Distribution Network**
