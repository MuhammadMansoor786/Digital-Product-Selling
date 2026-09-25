# Digital Product Selling Website

A full-stack PHP and MySQL e-commerce platform for selling digital products with a modern, responsive design.

## Features

### User Side
- **Authentication**: User registration and login with email
- **Landing Page**: Professional home page with featured products
- **Product Listing**: Browse products with category filtering and search
- **Product Details**: Detailed product pages with images and metadata
- **Checkout System**: Secure checkout with coupon support
- **User Dashboard**: View orders and download purchased files
- **Support System**: Create and manage support tickets

### Admin Side
- **Admin Authentication**: Secure admin login
- **User Management**: View users, block/unblock accounts
- **Product Management**: Add, edit, delete products with file uploads
- **Order Management**: View and manage customer orders
- **Payment Settings**: Configure payment gateways (Stripe, PayPal, etc.)
- **Coupon Management**: Create and manage discount codes
- **Support Management**: View and reply to support tickets
- **Reports & Analytics**: Dashboard with sales and performance metrics

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5.3
- **Icons**: Bootstrap Icons
- **Font**: Inter (Google Fonts)

## Installation

### Prerequisites
- XAMPP, WAMP, or any PHP/MySQL server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server

### Setup Instructions

1. **Clone or Download the Project**
   ```bash
   cd "C:\xampp\htdocs\"
   # Place the project folder here
   ```

2. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `digital_product_selling`
   - Import the `database.sql` file to create all required tables
   - Default admin credentials:
     - Email: `admin@digitalshop.com`
     - Password: `admin123`

3. **Configuration**
   - Edit `config/config.php` to update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'digital_product_selling');
   ```

4. **Update Site URL**
   - In `config/config.php`, update the SITE_URL to match your local setup:
   ```php
   define('SITE_URL', 'http://localhost/Digital%20Product%20Selling/');
   ```

5. **File Permissions**
   - Ensure the `uploads/` directory and its subdirectories are writable:
   - `uploads/products/`
   - `uploads/thumbnails/`

6. **Access the Application**
   - User Site: `http://localhost/Digital%20Product%20Selling/`
   - Admin Panel: `http://localhost/Digital%20Product%20Selling/admin/`

## Directory Structure

```
Digital Product Selling/
├── admin/                  # Admin panel files
│   ├── index.php          # Admin dashboard
│   ├── users.php          # User management
│   ├── products.php       # Product management
│   ├── orders.php         # Order management
│   ├── payments.php       # Payment settings
│   ├── coupons.php        # Coupon management
│   ├── support.php        # Support ticket management
│   ├── reports.php        # Reports and analytics
│   ├── login.php          # Admin login
│   └── logout.php         # Admin logout
├── config/                 # Configuration files
│   ├── config.php         # Main configuration
│   └── database.php       # Database connection class
├── includes/               # Reusable components
│   ├── header.php         # User site header
│   ├── footer.php         # User site footer
│   ├── admin-header.php   # Admin panel header
│   ├── admin-footer.php   # Admin panel footer
│   └── functions.php      # Helper functions
├── uploads/                # File upload directories
│   ├── products/          # Product files
│   └── thumbnails/        # Product thumbnails
├── index.php              # Home page
├── products.php           # Product listing
├── product.php            # Product details
├── checkout.php           # Checkout page
├── process-order.php      # Order processing
├── login.php              # User login
├── signup.php             # User registration
├── logout.php             # User logout
├── dashboard.php          # User dashboard
├── orders.php             # User orders
├── support.php            # Support tickets
├── support-reply.php      # Support ticket replies
├── database.sql           # Database schema
└── README.md              # This file
```

## Default Credentials

### Admin Account
- **Email**: admin@digitalshop.com
- **Password**: admin123

**Important**: Change the default admin password after first login!

## Usage Guide

### For Users

1. **Registration**
   - Click "Sign Up" on the navigation bar
   - Fill in your details and create a password
   - Verify your email (if email verification is enabled)

2. **Browsing Products**
   - Use the home page to see featured products
   - Visit the Products page for full catalog
   - Filter by category or search for specific items

3. **Purchasing**
   - Click on a product to view details
   - Click "Buy Now" to proceed to checkout
   - Apply coupon codes if available
   - Complete the payment process

4. **Downloads**
   - Access your dashboard after purchase
   - View your orders in the "My Orders" section
   - Download files from completed orders

5. **Support**
   - Create support tickets for any issues
   - Track ticket status and replies
   - Communicate with support team

### For Admins

1. **Dashboard**
   - View overall statistics
   - Monitor recent orders and tickets
   - Access quick links to all sections

2. **User Management**
   - View all registered users
   - Block/unblock user accounts
   - Search users by name or email

3. **Product Management**
   - Add new products with file uploads
   - Edit existing product details
   - Delete products
   - Manage product categories

4. **Order Management**
   - View all customer orders
   - Update order status
   - Manage payment status
   - View order details

5. **Payment Settings**
   - Configure payment gateways
   - Add API keys for Stripe, PayPal, etc.
   - Enable/disable payment methods

6. **Coupon Management**
   - Create discount codes
   - Set discount types (percentage/fixed)
   - Configure usage limits and validity periods

7. **Support Management**
   - View all support tickets
   - Reply to customer tickets
   - Update ticket status
   - Filter by priority and status

8. **Reports & Analytics**
   - View sales statistics
   - Analyze revenue trends
   - Track product performance
   - Monitor user activity

## Security Notes

- **Change Default Passwords**: Immediately change the default admin password
- **File Uploads**: File upload validation is implemented, but review allowed file types in `config/config.php`
- **SQL Injection**: All database queries use prepared statements
- **XSS Protection**: User input is sanitized using `htmlspecialchars()`
- **CSRF Protection**: CSRF tokens are implemented for form submissions
- **Session Security**: Session timeout is configured (1 hour default)

## Customization

### Changing the Design
- Edit CSS in `includes/header.php` and `includes/admin-header.php`
- Modify color variables in the `:root` CSS section
- Bootstrap 5 allows easy theme customization

### Adding New Features
- Follow the existing code structure
- Use the Database class for database operations
- Implement proper input sanitization
- Add appropriate error handling

### Payment Integration
- Currently uses a demo payment system
- Integrate real payment gateways in `checkout.php` and `process-order.php`
- Update payment settings in the admin panel

## Troubleshooting

### Database Connection Issues
- Verify MySQL is running
- Check database credentials in `config/config.php`
- Ensure the database exists and is properly imported

### File Upload Issues
- Check directory permissions for `uploads/` folder
- Verify PHP upload limits in `php.ini`
- Ensure file type validation is working

### Session Issues
- Check session save path permissions
- Verify session configuration in `config/config.php`
- Clear browser cookies if needed

### Admin Access Issues
- Verify admin credentials in database
- Check if admin account is blocked
- Clear browser cache and cookies

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Optimization

- Enable PHP OPcache for better performance
- Use a CDN for Bootstrap and font files
- Implement proper indexing on database tables
- Consider adding caching for frequently accessed data
- Optimize images before upload

## License

This project is provided as-is for educational and commercial use.

## Support

For issues or questions:
- Check the troubleshooting section
- Review the code comments
- Ensure all prerequisites are met

## Credits

- Built with PHP and MySQL
- UI framework: Bootstrap 5
- Icons: Bootstrap Icons
- Font: Inter (Google Fonts)

---

**Note**: This is a demo project. For production use, implement additional security measures, proper error logging, and consider using a framework like Laravel or Symfony for better security and maintainability.
