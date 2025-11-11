# API For Ninja Forms
Provides custom REST API endpoints to retrieve Ninja Forms submissions and field metadata with API key authentication and multiple export formats.
Supports JSON and PDF output.

Install via the WordPress repository here. [https://wordpress.org/plugins/api-for-ninja-forms](https://wordpress.org/plugins/api-for-ninja-forms)

## Description 
This plugin provides a robust and easy-to-use REST API for Ninja Forms, allowing you to integrate your form submissions with external applications and services.
With support for both JSON and PDF output formats, you can retrieve form data in a structured format submissions.

Features:

* JSON Output: Access your Ninja Forms data in a standardized JSON format, perfect for integration with web applications, CRMs, and other systems.
* PDF Output: Generate high-quality PDF documents from your form submissions, ideal for invoices, reports, or records.
* Secure API Keys: Easily generate and manage API keys to control access to your form data.
* Lightweight and Optimized: Designed for performance, ensuring minimal impact on your website's speed.

## Installation

1. Download the plugin ZIP file.
2. Upload the extracted folder to the /wp-content/plugins/ directory. Run composer install inside the api-for-ninja-forms folder to install required libraries.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Generate a REST API key by going to Settings > NF API Keys
5. Use the endpoints to request data by passing your API key via Authorization: Bearer e.g. https://yourwebsitehere.com/wp-json/nf-submissions/v1/form/2/
6. Exporting PDFs requires adding the optional format param to the url e.g.  https://yourwebsitehere.com/wp-json/nf-submissions/v1/form/2/?format=pdf
7. See more examples and API documentation on our [official docs page](https://sightfactory.com/plugins/api-for-ninjaforms/).

# Usage


## Frequently Asked Questions 

= What api file formats are supported? =
Currently, only JSON and PDF feeds are supported. Excel format is currently in development.

= How do I generate API keys? =
You can generate API keys directly from your WordPress dashboard. Navigate to Settings > NF API Keys to create and manage your keys.

= Does this plugin slow down my website? =
No, it is lightweight and optimized for performance, ensuring minimal impact on website speed.

== Third-Party Resources ==

This plugin bundles and utilizes the following open-source library:

* **Setasign/FPDF**
    * **Description:** A pure PHP library for reading and writing spreadsheet files.
    * **Homepage:** [https://github.com/Setasign/FPDF](https://github.com/Setasign/FPDF)
    * **License:** FPDF License (compatible with MIT/BSD-style)
    * **License URI:** [https://github.com/Setasign/FPDF?tab=License-1-ov-file#readme](https://github.com/Setasign/FPDF?tab=License-1-ov-file#readme)
