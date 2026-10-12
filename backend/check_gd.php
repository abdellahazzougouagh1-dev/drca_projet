<?php

if (extension_loaded('gd')) {
    echo "✅ PHP GD extension IS INSTALLED and active!\n";
    print_r(gd_info());
} else {
    echo "❌ PHP GD extension IS NOT INSTALLED or disabled in php.ini.\n";
}
