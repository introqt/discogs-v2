# LiveDG - Quick Installation & Setup Guide

## Installation Steps

### 1. Upload Plugin

**Via WordPress Admin:**
1. Navigate to **Plugins > Add New**
2. Click **Upload Plugin**
3. Choose the `live-dg.zip` file
4. Click **Install Now**
5. Click **Activate Plugin**

**Via FTP:**
1. Extract `live-dg.zip`
2. Upload the `live-dg` folder to `/wp-content/plugins/`
3. Navigate to **Plugins** in WordPress admin
4. Find "LiveDG" and click **Activate**

### 2. Verify Requirements

The plugin will check for:
- ✅ WordPress 6.0+
- ✅ PHP 8.4+
- ✅ WooCommerce installed and active

If WooCommerce is not active, you'll see an error message.

### 3. Get Discogs API Credentials

#### Option A: Personal Access Token (Recommended - Easiest)

1. Visit [Discogs Settings](https://www.discogs.com/settings/developers)
2. Log in to your Discogs account
3. Scroll to **Personal Access Token**
4. Click **Generate new token**
5. Copy the token (save it securely!)

#### Option B: OAuth Consumer Key/Secret

1. Visit [Discogs Settings](https://www.discogs.com/settings/developers)
2. Click **Create an Application**
3. Fill in application details:
   - **Application Name**: Your Store Name
   - **Application URL**: Your website URL
   - **Description**: Brief description
4. Copy the **Consumer Key** and **Consumer Secret**

### 4. Configure Plugin

1. Navigate to **LiveDG > Settings**
2. Enter your API credentials:
   - **Personal Access Token** (if using Option A)
   - OR **Consumer Key** and **Consumer Secret** (if using Option B)
3. Verify **User Agent** is auto-filled (e.g., "LiveDG/1.0 +https://yoursite.com")
4. Click **Save Settings**
5. Click **Test Connection** to verify credentials

### 5. Configure Import Settings

In **LiveDG > Settings**, configure:

- **SKU Prefix**: Default is "LDG" (products will have SKUs like "LDG-123456")
- **Default Product Status**: Choose "Draft" or "Published"
- **Import Images**: Enable to automatically import cover images
- **Auto Categorize**: Enable to create categories from genres
- **Enable Logging**: Enable for debugging (recommended)
- **Cache Duration**: Default 3600 seconds (1 hour)

Click **Save Settings**

### 6. Import Your First Product

1. Navigate to **LiveDG > Search Discogs**
2. Enter a search term (artist, album, or catalog number)
3. Browse results
4. Click **Import** on a release you want to add
5. In the import modal:
   - Set the **Price**
   - Choose **Product Status** (Draft/Published)
   - Enable **Manage Stock** if needed
   - Set **Stock Quantity** if managing stock
6. Click **Import Product**
7. Wait for success message
8. Navigate to **Products** to see your new product

## Post-Installation Checklist

- [ ] Plugin activated successfully
- [ ] WooCommerce is active
- [ ] API credentials configured
- [ ] Connection test passed
- [ ] Import settings configured
- [ ] First product imported successfully
- [ ] Product appears in WooCommerce
- [ ] Images imported correctly
- [ ] Categories created automatically

## Troubleshooting

### "WooCommerce is required" Error
- Install and activate WooCommerce first
- Visit **Plugins > Add New**
- Search for "WooCommerce"
- Install and activate

### "API Connection Failed"
- Double-check credentials
- Ensure no extra spaces in API keys
- Verify Discogs account is active
- Check server can make outbound HTTPS requests

### Images Not Importing
- Go to **LiveDG > Settings**
- Ensure "Import Images" is checked
- Check `wp-content/uploads` folder is writable
- Increase PHP memory limit if needed

### Products Import as Draft
- Go to **LiveDG > Settings**
- Change "Default Product Status" to "Published"
- Or manually publish from import modal

### Slow Imports
- Check internet connection
- Verify Discogs API is responding
- Check rate limits (60 requests/minute)
- Increase cache duration in settings

## Next Steps

### Customize Product Display
1. Go to **WooCommerce > Settings > Products**
2. Configure product display options
3. Set up shipping and tax rules

### Set Up Categories
- Products auto-categorize by genre
- Visit **Products > Categories** to manage
- Add descriptions and images to categories

### Configure Pricing
- Set prices during import
- Or bulk edit prices in **Products**
- Set up sale prices if needed

### Optimize Performance
1. **Enable Caching**
   - Already enabled by default
   - Adjust duration in settings if needed

2. **Monitor Logs**
   - Check **LiveDG > Logs** regularly
   - Export logs for debugging
   - Clear logs periodically

3. **Clear Cache**
   - Use **Clear Cache** button in settings
   - Or from dashboard quick actions

## Support & Documentation

- **Full Documentation**: See [README.md](README.md)
- **Testing Guide**: See [TESTING.md](TESTING.md)
- **Discogs API Docs**: https://www.discogs.com/developers
- **WooCommerce Docs**: https://woocommerce.com/documentation/

## Tips for Success

1. **Start with Draft Status**
   - Import products as drafts first
   - Review and edit before publishing
   - Ensures quality control

2. **Use Descriptive SKUs**
   - Set a meaningful SKU prefix
   - Makes inventory management easier

3. **Enable Logging**
   - Helpful for troubleshooting
   - Can disable later in production

4. **Test Thoroughly**
   - Import a few test products
   - Review product data
   - Adjust settings as needed

5. **Monitor API Usage**
   - Discogs has rate limits
   - Cache helps reduce API calls
   - Plan bulk imports accordingly

## Common Workflows

### Daily Operations
1. Search for new releases
2. Import products
3. Set prices and stock
4. Publish products

### Weekly Maintenance
1. Review logs for errors
2. Clear cache if needed
3. Update product information
4. Check inventory levels

### Monthly Tasks
1. Export and archive logs
2. Review and optimize settings
3. Update product descriptions
4. Add new categories/tags

## Getting Help

If you encounter issues:
1. Check **LiveDG > Logs** for errors
2. Review this guide and [README.md](README.md)
3. Consult [TESTING.md](TESTING.md) for troubleshooting
4. Check Discogs API status
5. Verify WordPress and WooCommerce are up to date

---

**Ready to Go!** You're all set to start importing music products from Discogs into your WooCommerce store. Happy selling! 🎵
