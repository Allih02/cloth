<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make a Sale - Enhanced Mobile Design</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS Variables for consistent theming */
        :root {
            --primary-color: #667eea;
            --primary-gradient: linear-gradient(135deg, #667eea, #764ba2);
            --success-color: #10b981;
            --success-gradient: linear-gradient(135deg, #10b981, #059669);
            --warning-color: #f59e0b;
            --warning-gradient: linear-gradient(135deg, #f59e0b, #d97706);
            --danger-color: #ef4444;
            --danger-gradient: linear-gradient(135deg, #ef4444, #dc2626);
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e8eaf6 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }

        /* Header */
        .page-header {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
        }

        .page-title i {
            padding: 0.75rem;
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--border-radius);
            font-size: 1.25rem;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-800);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Enhanced Mobile-First Product Selector */
        .product-selector-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .product-search-wrapper {
            position: relative;
        }

        .product-search-input {
            width: 100%;
            padding: 1rem 3.5rem 1rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
            touch-action: manipulation;
        }

        .product-search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .product-search-input::placeholder {
            color: var(--gray-400);
            font-style: italic;
        }

        .search-actions {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 0.25rem;
        }

        .search-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: var(--gray-100);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--gray-500);
            touch-action: manipulation;
        }

        .search-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .search-btn.active {
            background: var(--primary-color);
            color: white;
        }

        /* Mobile-Optimized Dropdown */
        .dropdown-panel {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid var(--gray-200);
            border-top: none;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            max-height: 70vh;
            overflow: hidden;
            z-index: 1000;
            display: none;
            box-shadow: var(--shadow-xl);
        }

        .dropdown-panel.show {
            display: block;
            animation: dropdownSlideIn 0.3s ease;
        }

        @keyframes dropdownSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced Mobile Quick Filters */
        .quick-filters {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-100);
            background: var(--gray-50);
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.75rem;
            display: block;
        }

        .filter-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.75rem 0.5rem;
            border: 2px solid var(--gray-200);
            background: white;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            text-align: center;
            touch-action: manipulation;
        }

        .filter-btn:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .filter-btn.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }

        .filter-btn i {
            font-size: 1rem;
        }

        /* Mobile-Optimized Product List */
        .products-container {
            max-height: 50vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .products-header {
            padding: 0.75rem 1rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.875rem;
            color: var(--gray-600);
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .category-group {
            border-bottom: 1px solid var(--gray-100);
        }

        .category-header {
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 40px;
            z-index: 9;
        }

        .product-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid var(--gray-50);
            gap: 1rem;
            touch-action: manipulation;
            min-height: 80px;
        }

        .product-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .product-item.selected {
            background: rgba(102, 126, 234, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .product-item.focused {
            background: rgba(102, 126, 234, 0.08);
            border-left: 4px solid var(--primary-color);
        }

        .product-image {
            position: relative;
            flex-shrink: 0;
        }

        .image-placeholder {
            width: 48px;
            height: 48px;
            background: var(--primary-gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .stock-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 20px;
            height: 20px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 0 4px;
        }

        .stock-badge.high { background: var(--success-color); }
        .stock-badge.medium { background: var(--warning-color); }
        .stock-badge.low { background: var(--danger-color); }

        .product-details {
            flex: 1;
            min-width: 0;
        }

        .product-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
            color: var(--gray-500);
            flex-wrap: wrap;
        }

        .product-meta .separator {
            color: var(--gray-300);
        }

        .product-price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .product-actions {
            display: flex;
            flex-direction: column;
            align-items: end;
            gap: 0.5rem;
        }

        .stock-status {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stock-status.high {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .stock-status.medium {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .stock-status.low {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .select-btn {
            padding: 0.5rem 0.75rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            touch-action: manipulation;
        }

        .select-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Selected Product Display */
        .selected-product {
            margin-top: 0.75rem;
            padding: 1rem;
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid var(--success-color);
            border-radius: var(--border-radius);
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            animation: selectedFadeIn 0.3s ease;
        }

        .selected-product.show {
            display: flex;
        }

        @keyframes selectedFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .selected-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 0;
        }

        .selected-image {
            width: 40px;
            height: 40px;
            background: var(--success-gradient);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .selected-details {
            flex: 1;
            min-width: 0;
        }

        .selected-name {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .selected-meta {
            font-size: 0.85rem;
            color: var(--gray-600);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .change-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 2px solid var(--success-color);
            color: var(--success-color);
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
            touch-action: manipulation;
        }

        .change-btn:hover {
            background: var(--success-color);
            color: white;
        }

        /* Form Layout - Mobile First */
        .form-container {
            display: grid;
            gap: 1.5rem;
        }

        .form-section {
            background: var(--gray-50);
            border-radius: var(--border-radius);
            padding: 1.25rem;
        }

        .form-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section-title i {
            color: var(--primary-color);
        }

        /* Enhanced Input Controls */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            touch-action: manipulation;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Enhanced Quantity Input */
        .quantity-container {
            display: flex;
            align-items: center;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            overflow: hidden;
            background: white;
        }

        .quantity-container:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .quantity-btn {
            width: 48px;
            height: 48px;
            border: none;
            background: var(--gray-50);
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            touch-action: manipulation;
        }

        .quantity-btn:hover:not(:disabled) {
            background: var(--primary-color);
            color: white;
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .quantity-input {
            flex: 1;
            border: none;
            padding: 0.75rem;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            background: transparent;
            min-width: 60px;
        }

        .quantity-input:focus {
            outline: none;
        }

        /* Enhanced Price Input */
        .price-container {
            position: relative;
        }

        .price-input {
            padding-right: 4rem;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            font-size: 1.1rem;
            font-weight: 600;
            text-align: right;
        }

        .currency-symbol {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-weight: 600;
            pointer-events: none;
        }

        .suggested-price {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: rgba(245, 158, 11, 0.1);
            border-radius: 8px;
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            animation: fadeInUp 0.3s ease;
        }

        .suggested-price.show {
            display: flex;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .suggested-price-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--warning-color);
            font-weight: 500;
        }

        .use-suggested-btn {
            padding: 0.5rem 1rem;
            background: var(--warning-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            touch-action: manipulation;
        }

        .use-suggested-btn:hover {
            background: #d97706;
        }

        /* Enhanced Payment Methods */
        .payment-methods {
            display: grid;
            gap: 0.75rem;
        }

        .payment-option {
            position: relative;
        }

        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .payment-label {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            gap: 1rem;
            position: relative;
            touch-action: manipulation;
        }

        .payment-label:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .payment-option input[type="radio"]:checked + .payment-label {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .payment-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .payment-icon.cash {
            background: var(--warning-gradient);
            color: white;
        }

        .payment-icon.mobile {
            background: var(--success-gradient);
            color: white;
        }

        .payment-icon.bank {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .payment-info {
            flex: 1;
            min-width: 0;
        }

        .payment-name {
            font-weight: 700;
            font-size: 1rem;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .payment-desc {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .payment-check {
            width: 24px;
            height: 24px;
            border: 2px solid var(--gray-300);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .payment-option input[type="radio"]:checked + .payment-label .payment-check {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }

        /* Enhanced Total Display */
        .total-section {
            background: var(--success-gradient);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .total-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: shimmer 4s infinite;
        }

        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .total-label {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .total-amount {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 0.25rem;
            position: relative;
            z-index: 1;
            line-height: 1;
        }

        .total-currency {
            font-size: 1rem;
            font-weight: 600;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .total-breakdown {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.15);
            border-radius: var(--border-radius);
            backdrop-filter: blur(10px);
            display: none;
        }

        .total-breakdown.show {
            display: block;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .breakdown-item.total {
            font-weight: 700;
            font-size: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            margin-top: 0.5rem;
        }

        /* Action Buttons */
        .form-actions {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            touch-action: manipulation;
            min-height: 52px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--success-gradient);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--gray-600), var(--gray-700));
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Loading States */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Alerts and Notifications */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #065f46;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #991b1b;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #92400e;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #1e40af;
        }

        .alert i {
            font-size: 1.25rem;
            margin-top: 0.125rem;
        }

        /* Stock Warning */
        .stock-warning {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--danger-color);
            display: none;
            align-items: center;
            gap: 0.5rem;
            animation: warningPulse 2s infinite;
        }

        .stock-warning.show {
            display: flex;
        }

        @keyframes warningPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* No Results */
        .no-results {
            padding: 3rem 2rem;
            text-align: center;
            color: var(--gray-500);
        }

        .no-results-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .no-results h4 {
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 1.125rem;
        }

        .no-results p {
            margin-bottom: 1.5rem;
            color: var(--gray-500);
        }

        .btn-clear-search {
            padding: 0.75rem 1.5rem;
            background: var(--gray-100);
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            color: var(--gray-700);
        }

        .btn-clear-search:hover {
            background: var(--gray-200);
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat-card {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            background: var(--primary-gradient);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 900;
            color: var(--gray-800);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Toast Notifications */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-xl);
            padding: 1rem 1.5rem;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            max-width: 400px;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-success {
            border-left: 4px solid var(--success-color);
        }

        .toast-error {
            border-left: 4px solid var(--danger-color);
        }

        .toast-warning {
            border-left: 4px solid var(--warning-color);
        }

        .toast-info {
            border-left: 4px solid var(--primary-color);
        }

        .toast-icon {
            font-size: 1.25rem;
        }

        .toast-success .toast-icon { color: var(--success-color); }
        .toast-error .toast-icon { color: var(--danger-color); }
        .toast-warning .toast-icon { color: var(--warning-color); }
        .toast-info .toast-icon { color: var(--primary-color); }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--gray-800);
        }

        .toast-message {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .toast-close:hover {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        /* Responsive Design */
        @media (min-width: 640px) {
            .container {
                padding: 1.5rem;
            }

            .form-container {
                grid-template-columns: 1fr 1fr;
                gap: 2rem;
            }

            .form-actions {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .filter-buttons {
                grid-template-columns: repeat(4, 1fr);
            }

            .payment-methods {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .total-amount {
                font-size: 3rem;
            }
        }

        @media (min-width: 768px) {
            .page-header {
                padding: 2rem;
            }

            .card-body {
                padding: 2rem;
            }

            .dropdown-panel {
                max-height: 60vh;
            }

            .products-container {
                max-height: 45vh;
            }

            .product-item {
                grid-template-columns: auto 1fr auto;
                min-height: 90px;
            }

            .product-name {
                white-space: normal;
                overflow: visible;
                text-overflow: unset;
            }
        }

        @media (min-width: 1024px) {
            .form-container {
                grid-template-columns: 2fr 1fr;
                gap: 3rem;
            }

            .dropdown-panel {
                max-height: 500px;
            }

            .products-container {
                max-height: 400px;
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            :root {
                --gray-50: #111827;
                --gray-100: #1f2937;
                --gray-200: #374151;
                --gray-300: #4b5563;
                --gray-400: #6b7280;
                --gray-500: #9ca3af;
                --gray-600: #d1d5db;
                --gray-700: #e5e7eb;
                --gray-800: #f3f4f6;
                --gray-900: #f9fafb;
            }

            body {
                background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            }

            .card,
            .form-control,
            .product-search-input,
            .payment-label,
            .selected-product {
                background: var(--gray-100);
                color: var(--gray-800);
            }
        }

        /* Accessibility Improvements */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Focus Styles */
        *:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* High Contrast Mode */
        @media (prefers-contrast: high) {
            .btn,
            .form-control,
            .payment-label {
                border-width: 3px;
            }
        }

        /* Touch Improvements */
        @media (hover: none) and (pointer: coarse) {
            .product-item:hover,
            .btn:hover,
            .search-btn:hover {
                transform: none;
            }

            .product-item {
                padding: 1.25rem;
            }

            .btn {
                min-height: 56px;
                padding: 1.25rem 2rem;
            }

            .search-btn {
                width: 44px;
                height: 44px;
            }

            .quantity-btn {
                width: 56px;
                height: 56px;
            }
        }

        /* Print Styles */
        @media print {
            .search-actions,
            .form-actions,
            .header-actions {
                display: none;
            }

            .card {
                box-shadow: none;
                border: 1px solid var(--gray-300);
            }

            .page-header {
                background: transparent;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-shopping-cart"></i>
                Make a Sale
            </div>
            <div class="header-actions">
                <a href="#" class="btn btn-outline">
                    <i class="fas fa-history"></i>
                    <span class="sr-only">View </span>Sales History
                </a>
            </div>
        </div>

        <!-- Alerts Section -->
        <div id="alerts-container">
            <!-- Success Message -->
            <div class="alert alert-success" style="display: none;" id="success-alert">
                <i class="fas fa-check-circle"></i>
                <div id="success-message"></div>
            </div>

            <!-- Error Messages -->
            <div class="alert alert-danger" style="display: none;" id="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <div id="error-messages"></div>
            </div>
        </div>

        <!-- Main Sale Form -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-credit-card"></i> Process Sale Transaction</h3>
            </div>
            <div class="card-body">
                <form id="sales-form" method="POST" action="">
                    <div class="form-container">
                        <!-- Left Column: Product & Quantity -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-tshirt"></i>
                                Product Selection
                            </div>

                            <!-- Enhanced Product Selector -->
                            <div class="product-selector-container">
                                <label for="product-search" class="form-label">
                                    Select Jersey, Cap or Shorts *
                                    <small style="color: var(--gray-500); font-weight: normal; margin-left: 0.5rem;">
                                        Click to browse or start typing to search
                                    </small>
                                </label>

                                <div class="product-search-wrapper">
                                    <input 
                                        type="text" 
                                        id="product-search" 
                                        class="product-search-input" 
                                        placeholder="🔍 Search jerseys, caps, shorts..."
                                        autocomplete="off"
                                        spellcheck="false"
                                        role="combobox"
                                        aria-expanded="false"
                                        aria-haspopup="listbox"
                                        aria-label="Search products"
                                    >
                                    
                                    <div class="search-actions">
                                        <button type="button" class="search-btn" id="clear-search" title="Clear search" aria-label="Clear search">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button type="button" class="search-btn" id="toggle-dropdown" title="Browse all products" aria-label="Browse all products">
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Dropdown Panel -->
                                <div class="dropdown-panel" id="dropdown-panel" role="listbox" aria-label="Product list">
                                    <!-- Quick Filters -->
                                    <div class="quick-filters">
                                        <div class="filter-label">Quick Filter:</div>
                                        <div class="filter-buttons">
                                            <button type="button" class="filter-btn active" data-category="" aria-label="Show all products">
                                                <i class="fas fa-list"></i>
                                                <span>All</span>
                                            </button>
                                            <button type="button" class="filter-btn" data-category="Men's Clothing" aria-label="Show men's clothing">
                                                <i class="fas fa-male"></i>
                                                <span>Men's</span>
                                            </button>
                                            <button type="button" class="filter-btn" data-category="Women's Clothing" aria-label="Show women's clothing">
                                                <i class="fas fa-female"></i>
                                                <span>Women's</span>
                                            </button>
                                            <button type="button" class="filter-btn" data-category="Children's Clothing" aria-label="Show children's clothing">
                                                <i class="fas fa-child"></i>
                                                <span>Kids</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Products Container -->
                                    <div class="products-container">
                                        <div class="products-header">
                                            <div id="results-count">Loading products...</div>
                                        </div>

                                        <div id="products-list">
                                            <!-- Sample Categories and Products -->
                                            <div class="category-group" data-category="Men's Clothing">
                                                <div class="category-header">
                                                    <i class="fas fa-tag"></i>
                                                    Men's Clothing
                                                </div>

                                                <div class="product-item" 
                                                     data-product-id="1"
                                                     data-name="Manchester United Home Jersey"
                                                     data-price="85000"
                                                     data-stock="25"
                                                     data-category="Men's Clothing"
                                                     data-size="L"
                                                     data-color="Red"
                                                     data-brand="Adidas"
                                                     data-search="manchester united home jersey adidas red l men's clothing"
                                                     role="option"
                                                     tabindex="0">
                                                    
                                                    <div class="product-image">
                                                        <div class="image-placeholder">
                                                            <i class="fas fa-tshirt"></i>
                                                        </div>
                                                        <div class="stock-badge high">25</div>
                                                    </div>
                                                    
                                                    <div class="product-details">
                                                        <div class="product-name">Manchester United Home Jersey</div>
                                                        <div class="product-meta">
                                                            <span class="brand">Adidas</span>
                                                            <span class="separator">•</span>
                                                            <span>Size: L</span>
                                                            <span class="separator">•</span>
                                                            <span class="color">
                                                                <span class="color-dot" style="background-color: red"></span>
                                                                Red
                                                            </span>
                                                        </div>
                                                        <div class="product-price">85,000 TZS</div>
                                                    </div>
                                                    
                                                    <div class="product-actions">
                                                        <div class="stock-status high">25 in stock</div>
                                                        <button type="button" class="select-btn">
                                                            <i class="fas fa-check"></i> Select
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="product-item" 
                                                     data-product-id="2"
                                                     data-name="Barcelona Away Jersey"
                                                     data-price="80000"
                                                     data-stock="15"
                                                     data-category="Men's Clothing"
                                                     data-size="M"
                                                     data-color="Blue"
                                                     data-brand="Nike"
                                                     data-search="barcelona away jersey nike blue m men's clothing"
                                                     role="option"
                                                     tabindex="0">
                                                    
                                                    <div class="product-image">
                                                        <div class="image-placeholder">
                                                            <i class="fas fa-tshirt"></i>
                                                        </div>
                                                        <div class="stock-badge medium">15</div>
                                                    </div>
                                                    
                                                    <div class="product-details">
                                                        <div class="product-name">Barcelona Away Jersey</div>
                                                        <div class="product-meta">
                                                            <span class="brand">Nike</span>
                                                            <span class="separator">•</span>
                                                            <span>Size: M</span>
                                                            <span class="separator">•</span>
                                                            <span class="color">
                                                                <span class="color-dot" style="background-color: blue"></span>
                                                                Blue
                                                            </span>
                                                        </div>
                                                        <div class="product-price">80,000 TZS</div>
                                                    </div>
                                                    
                                                    <div class="product-actions">
                                                        <div class="stock-status medium">15 in stock</div>
                                                        <button type="button" class="select-btn">
                                                            <i class="fas fa-check"></i> Select
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="category-group" data-category="Women's Clothing">
                                                <div class="category-header">
                                                    <i class="fas fa-tag"></i>
                                                    Women's Clothing
                                                </div>

                                                <div class="product-item" 
                                                     data-product-id="3"
                                                     data-name="Chelsea Women's Home Jersey"
                                                     data-price="75000"
                                                     data-stock="8"
                                                     data-category="Women's Clothing"
                                                     data-size="S"
                                                     data-color="Blue"
                                                     data-brand="Nike"
                                                     data-search="chelsea women's home jersey nike blue s women's clothing"
                                                     role="option"
                                                     tabindex="0">
                                                    
                                                    <div class="product-image">
                                                        <div class="image-placeholder">
                                                            <i class="fas fa-tshirt"></i>
                                                        </div>
                                                        <div class="stock-badge medium">8</div>
                                                    </div>
                                                    
                                                    <div class="product-details">
                                                        <div class="product-name">Chelsea Women's Home Jersey</div>
                                                        <div class="product-meta">
                                                            <span class="brand">Nike</span>
                                                            <span class="separator">•</span>
                                                            <span>Size: S</span>
                                                            <span class="separator">•</span>
                                                            <span class="color">
                                                                <span class="color-dot" style="background-color: blue"></span>
                                                                Blue
                                                            </span>
                                                        </div>
                                                        <div class="product-price">75,000 TZS</div>
                                                    </div>
                                                    
                                                    <div class="product-actions">
                                                        <div class="stock-status medium">8 in stock</div>
                                                        <button type="button" class="select-btn">
                                                            <i class="fas fa-check"></i> Select
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="category-group" data-category="Accessories">
                                                <div class="category-header">
                                                    <i class="fas fa-tag"></i>
                                                    Accessories
                                                </div>

                                                <div class="product-item" 
                                                     data-product-id="4"
                                                     data-name="Arsenal Baseball Cap"
                                                     data-price="25000"
                                                     data-stock="3"
                                                     data-category="Accessories"
                                                     data-size="One Size"
                                                     data-color="Red"
                                                     data-brand="Puma"
                                                     data-search="arsenal baseball cap puma red one size accessories"
                                                     role="option"
                                                     tabindex="0">
                                                    
                                                    <div class="product-image">
                                                        <div class="image-placeholder">
                                                            <i class="fas fa-baseball-ball"></i>
                                                        </div>
                                                        <div class="stock-badge low">3</div>
                                                    </div>
                                                    
                                                    <div class="product-details">
                                                        <div class="product-name">Arsenal Baseball Cap</div>
                                                        <div class="product-meta">
                                                            <span class="brand">Puma</span>
                                                            <span class="separator">•</span>
                                                            <span>Size: One Size</span>
                                                            <span class="separator">•</span>
                                                            <span class="color">
                                                                <span class="color-dot" style="background-color: red"></span>
                                                                Red
                                                            </span>
                                                        </div>
                                                        <div class="product-price">25,000 TZS</div>
                                                    </div>
                                                    
                                                    <div class="product-actions">
                                                        <div class="stock-status low">3 in stock</div>
                                                        <button type="button" class="select-btn">
                                                            <i class="fas fa-check"></i> Select
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- No Results -->
                                        <div class="no-results" id="no-results" style="display: none;">
                                            <div class="no-results-icon">
                                                <i class="fas fa-search"></i>
                                            </div>
                                            <div class="no-results-text">
                                                <h4>No products found</h4>
                                                <p>Try adjusting your search terms or browse all products</p>
                                            </div>
                                            <button type="button" class="btn-clear-search" onclick="clearSearch()">
                                                <i class="fas fa-times"></i> Clear Search
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Product Display -->
                                <div class="selected-product" id="selected-product">
                                    <div class="selected-info">
                                        <div class="selected-image">
                                            <i class="fas fa-tshirt"></i>
                                        </div>
                                        <div class="selected-details">
                                            <div class="selected-name" id="selected-name"></div>
                                            <div class="selected-meta" id="selected-meta"></div>
                                        </div>
                                    </div>
                                    <button type="button" class="change-btn" id="change-product">
                                        <i class="fas fa-edit"></i> Change
                                    </button>
                                </div>

                                <!-- Hidden Select for Form Submission -->
                                <select id="product_id" name="product_id" style="display: none;" required>
                                    <option value="">Choose Product</option>
                                    <option value="1" data-price="85000" data-stock="25" data-name="Manchester United Home Jersey">Manchester United Home Jersey</option>
                                    <option value="2" data-price="80000" data-stock="15" data-name="Barcelona Away Jersey">Barcelona Away Jersey</option>
                                    <option value="3" data-price="75000" data-stock="8" data-name="Chelsea Women's Home Jersey">Chelsea Women's Home Jersey</option>
                                    <option value="4" data-price="25000" data-stock="3" data-name="Arsenal Baseball Cap">Arsenal Baseball Cap</option>
                                </select>
                            </div>

                            <!-- Quantity Input -->
                            <div class="form-group">
                                <label for="quantity" class="form-label">
                                    <i class="fas fa-sort-numeric-up"></i> Quantity *
                                </label>
                                <div class="quantity-container">
                                    <button type="button" class="quantity-btn" id="quantity-minus" disabled>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input 
                                        type="number" 
                                        id="quantity" 
                                        name="quantity" 
                                        class="quantity-input" 
                                        min="1" 
                                        required 
                                        placeholder="0"
                                        aria-label="Quantity"
                                    >
                                    <button type="button" class="quantity-btn" id="quantity-plus" disabled>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="stock-warning" id="stock-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span class="warning-text"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Price & Payment -->
                        <div class="form-section">
                            <div class="form-section-title">
                                <i class="fas fa-money-bill-wave"></i>
                                Price & Payment
                            </div>

                            <!-- Unit Price Input -->
                            <div class="form-group">
                                <label for="unit_price" class="form-label">
                                    <i class="fas fa-money-bill-wave"></i> Unit Price (TZS) *
                                </label>
                                <div class="price-container">
                                    <input 
                                        type="text" 
                                        id="unit_price" 
                                        name="unit_price" 
                                        class="form-control price-input" 
                                        required 
                                        placeholder="0"
                                        aria-label="Unit price in TZS"
                                    >
                                    <span class="currency-symbol">TZS</span>
                                </div>
                                <div class="suggested-price" id="suggested-price">
                                    <div class="suggested-price-info">
                                        <i class="fas fa-lightbulb"></i>
                                        <span>Suggested: <span id="suggested-price-value"></span></span>
                                    </div>
                                    <button type="button" class="use-suggested-btn" id="use-suggested-price">
                                        Use This Price
                                    </button>
                                </div>
                            </div>

                            <!-- Payment Method Selection -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-credit-card"></i> Payment Method *
                                </label>
                                <div class="payment-methods">
                                    <div class="payment-option">
                                        <input type="radio" id="cash" name="payment_method" value="CASH" required>
                                        <label for="cash" class="payment-label">
                                            <div class="payment-icon cash">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </div>
                                            <div class="payment-info">
                                                <div class="payment-name">CASH</div>
                                                <div class="payment-desc">Cash Payment</div>
                                            </div>
                                            <div class="payment-check">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="payment-option">
                                        <input type="radio" id="lipa" name="payment_method" value="LIPA NUMBER" required>
                                        <label for="lipa" class="payment-label">
                                            <div class="payment-icon mobile">
                                                <i class="fas fa-mobile-alt"></i>
                                            </div>
                                            <div class="payment-info">
                                                <div class="payment-name">LIPA NUMBER</div>
                                                <div class="payment-desc">Mobile Money Payment</div>
                                            </div>
                                            <div class="payment-check">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="payment-option">
                                        <input type="radio" id="crdb" name="payment_method" value="CRDB BANK" required>
                                        <label for="crdb" class="payment-label">
                                            <div class="payment-icon bank">
                                                <i class="fas fa-university"></i>
                                            </div>
                                            <div class="payment-info">
                                                <div class="payment-name">CRDB BANK</div>
                                                <div class="payment-desc">Bank Transfer</div>
                                            </div>
                                            <div class="payment-check">
                                                <i class="fas fa-check"></i>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Amount Display -->
                            <div class="form-group">
                                <div class="total-section" id="total-display">
                                    <div class="total-label">Total Amount</div>
                                    <div class="total-amount" id="total-amount">0</div>
                                    <div class="total-currency">TZS</div>
                                    
                                    <div class="total-breakdown" id="total-breakdown">
                                        <div class="breakdown-item">
                                            <span>Unit Price:</span>
                                            <span id="breakdown-unit-price">0 TZS</span>
                                        </div>
                                        <div class="breakdown-item">
                                            <span>Quantity:</span>
                                            <span id="breakdown-quantity">0</span>
                                        </div>
                                        <div class="breakdown-item total">
                                            <span>Total:</span>
                                            <span id="breakdown-total">0 TZS</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <div style="display: flex; gap: 1rem; flex: 1;">
                            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                                <i class="fas fa-shopping-cart"></i>
                                Complete Sale
                            </button>
                            <button type="button" class="btn btn-secondary" id="reset-btn">
                                <i class="fas fa-redo"></i>
                                Reset Form
                            </button>
                        </div>
                        <div class="keyboard-shortcuts" style="color: var(--gray-500); font-size: 0.875rem;">
                            <i class="fas fa-keyboard"></i>
                            <span class="sr-only">Keyboard shortcuts: </span>
                            F2: Search • F3: Quantity • F4: Price
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Today's Sales Statistics -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> Today's Sales Statistics</h3>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="total-sales">24</div>
                            <div class="stat-label">Total Sales</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="total-revenue">1,850,000</div>
                            <div class="stat-label">Revenue (TZS)</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value" id="items-sold">47</div>
                            <div class="stat-label">Items Sold</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script>
        // Enhanced Product Selector Class
        class EnhancedProductSelector {
            constructor() {
                this.searchInput = document.getElementById('product-search');
                this.toggleButton = document.getElementById('toggle-dropdown');
                this.clearButton = document.getElementById('clear-search');
                this.dropdownPanel = document.getElementById('dropdown-panel');
                this.productsList = document.getElementById('products-list');
                this.selectedProduct = document.getElementById('selected-product');
                this.hiddenSelect = document.getElementById('product_id');
                this.resultsCount = document.getElementById('results-count');
                this.noResults = document.getElementById('no-results');
                
                this.isOpen = false;
                this.focusedIndex = -1;
                this.selectedProductData = null;
                this.allProducts = [];
                this.filteredProducts = [];
                this.activeFilter = '';
                
                this.init();
            }
            
            init() {
                this.collectProducts();
                this.bindEvents();
                this.updateResultsCount();
            }
            
            collectProducts() {
                const productItems = this.productsList.querySelectorAll('.product-item');
                this.allProducts = Array.from(productItems).map(item => ({
                    element: item,
                    id: item.dataset.productId,
                    name: item.dataset.name,
                    price: parseFloat(item.dataset.price),
                    stock: parseInt(item.dataset.stock),
                    category: item.dataset.category,
                    size: item.dataset.size,
                    color: item.dataset.color,
                    brand: item.dataset.brand,
                    searchText: item.dataset.search
                }));
                this.filteredProducts = [...this.allProducts];
            }
            
            bindEvents() {
                // Search input events
                this.searchInput.addEventListener('click', () => this.open());
                this.searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
                this.searchInput.addEventListener('keydown', (e) => this.handleKeyDown(e));
                this.searchInput.addEventListener('focus', () => this.open());
                
                // Toggle and clear buttons
                this.toggleButton.addEventListener('click', () => this.toggle());
                this.clearButton.addEventListener('click', () => this.clearSearch());
                
                // Product selection
                this.productsList.addEventListener('click', (e) => {
                    const productItem = e.target.closest('.product-item');
                    if (productItem) {
                        this.selectProduct(productItem);
                    }
                });
                
                // Product keyboard navigation
                this.productsList.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const focusedProduct = e.target.closest('.product-item');
                        if (focusedProduct) {
                            this.selectProduct(focusedProduct);
                        }
                    }
                });
                
                // Quick filters
                const filterBtns = document.querySelectorAll('.filter-btn');
                filterBtns.forEach(btn => {
                    btn.addEventListener('click', () => this.applyFilter(btn.dataset.category));
                });
                
                // Change product button
                document.getElementById('change-product').addEventListener('click', () => this.changeProduct());
                
                // Close on outside click
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.product-selector-container')) {
                        this.close();
                    }
                });
                
                // Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isOpen) {
                        this.close();
                        this.searchInput.focus();
                    }
                });
            }
            
            open() {
                this.isOpen = true;
                this.dropdownPanel.classList.add('show');
                this.toggleButton.classList.add('active');
                this.searchInput.setAttribute('aria-expanded', 'true');
                this.focusedIndex = -1;
                this.updateFocusedProduct();
            }
            
            close() {
                this.isOpen = false;
                this.dropdownPanel.classList.remove('show');
                this.toggleButton.classList.remove('active');
                this.searchInput.setAttribute('aria-expanded', 'false');
                this.focusedIndex = -1;
                this.updateFocusedProduct();
            }
            
            toggle() {
                this.isOpen ? this.close() : this.open();
            }
            
            handleSearch(searchTerm) {
                if (!searchTerm.trim()) {
                    this.showAllProducts();
                    this.clearButton.style.display = 'none';
                    return;
                }
                
                this.filterProducts(searchTerm);
                this.clearButton.style.display = 'flex';
                
                if (!this.isOpen) this.open();
            }
            
            filterProducts(searchTerm) {
                const term = searchTerm.toLowerCase();
                
                this.filteredProducts = this.allProducts.filter(product => 
                    product.searchText.includes(term) ||
                    product.name.toLowerCase().includes(term) ||
                    product.brand.toLowerCase().includes(term) ||
                    product.category.toLowerCase().includes(term)
                );
                
                this.displayFilteredProducts();
                this.focusedIndex = -1;
                this.updateFocusedProduct();
            }
            
            applyFilter(category) {
                // Update filter button states
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.category === category);
                });
                
                this.activeFilter = category;
                
                if (!category) {
                    this.showAllProducts();
                } else {
                    this.filteredProducts = this.allProducts.filter(product => 
                        product.category === category
                    );
                    this.displayFilteredProducts();
                }
                
                if (!this.isOpen) this.open();
            }
            
            showAllProducts() {
                this.filteredProducts = [...this.allProducts];
                this.displayFilteredProducts();
            }
            
            displayFilteredProducts() {
                // Hide all products and categories first
                this.allProducts.forEach(product => {
                    product.element.style.display = 'none';
                });
                
                // Hide all category groups
                const categoryGroups = this.productsList.querySelectorAll('.category-group');
                categoryGroups.forEach(group => {
                    group.style.display = 'none';
                });
                
                // Show filtered products and their categories
                const visibleCategories = new Set();
                this.filteredProducts.forEach(product => {
                    product.element.style.display = 'grid';
                    visibleCategories.add(product.category);
                });
                
                // Show relevant category groups
                categoryGroups.forEach(group => {
                    if (visibleCategories.has(group.dataset.category)) {
                        group.style.display = 'block';
                    }
                });
                
                this.updateResultsCount();
                
                // Show/hide no results
                this.noResults.style.display = this.filteredProducts.length === 0 ? 'block' : 'none';
            }
            
            updateResultsCount() {
                const count = this.filteredProducts.length;
                this.resultsCount.textContent = `${count} product${count !== 1 ? 's' : ''} found`;
            }
            
            handleKeyDown(e) {
                if (!this.isOpen) return;
                
                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        this.focusNext();
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        this.focusPrevious();
                        break;
                    case 'Enter':
                        e.preventDefault();
                        if (this.focusedIndex >= 0 && this.filteredProducts[this.focusedIndex]) {
                            this.selectProduct(this.filteredProducts[this.focusedIndex].element);
                        }
                        break;
                    case 'Tab':
                        this.close();
                        break;
                }
            }
            
            focusNext() {
                if (this.filteredProducts.length === 0) return;
                this.focusedIndex = (this.focusedIndex + 1) % this.filteredProducts.length;
                this.updateFocusedProduct();
            }
            
            focusPrevious() {
                if (this.filteredProducts.length === 0) return;
                this.focusedIndex = this.focusedIndex <= 0 ? 
                    this.filteredProducts.length - 1 : this.focusedIndex - 1;
                this.updateFocusedProduct();
            }
            
            updateFocusedProduct() {
                // Remove focus from all products
                this.allProducts.forEach(product => {
                    product.element.classList.remove('focused');
                });
                
                // Add focus to current product
                if (this.focusedIndex >= 0 && this.filteredProducts[this.focusedIndex]) {
                    const focusedProduct = this.filteredProducts[this.focusedIndex];
                    focusedProduct.element.classList.add('focused');
                    focusedProduct.element.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            }
            
            selectProduct(productElement) {
                const productData = this.allProducts.find(p => p.element === productElement);
                if (!productData) return;
                
                // Update selected product data
                this.selectedProductData = productData;
                
                // Update hidden select
                this.hiddenSelect.value = productData.id;
                
                // Update UI
                this.updateSelectedProductDisplay();
                
                // Hide search and show selected product
                this.close();
                this.searchInput.style.display = 'none';
                this.selectedProduct.classList.add('show');
                
                // Clear search
                this.searchInput.value = '';
                this.clearButton.style.display = 'none';
                
                // Trigger change event
                this.hiddenSelect.dispatchEvent(new Event('change'));
                
                // Focus next input
                document.getElementById('quantity').focus();
                
                // Show success notification
                this.showNotification('Product selected successfully!', 'success');
            }
            
            updateSelectedProductDisplay() {
                if (!this.selectedProductData) return;
                
                const selectedName = document.getElementById('selected-name');
                const selectedMeta = document.getElementById('selected-meta');
                
                selectedName.textContent = this.selectedProductData.name;
                selectedMeta.innerHTML = `
                    <span class="brand">${this.selectedProductData.brand}</span>
                    <span class="separator">•</span>
                    <span>Size: ${this.selectedProductData.size}</span>
                    <span class="separator">•</span>
                    <span class="color">
                        <span class="color-dot" style="background-color: ${this.selectedProductData.color.toLowerCase()}"></span>
                        ${this.selectedProductData.color}
                    </span>
                    <span class="separator">•</span>
                    <span class="price">${this.formatPrice(this.selectedProductData.price)} TZS</span>
                `;
            }
            
            changeProduct() {
                this.selectedProduct.classList.remove('show');
                this.searchInput.style.display = 'block';
                this.searchInput.focus();
                this.selectedProductData = null;
                this.hiddenSelect.value = '';
                this.hiddenSelect.dispatchEvent(new Event('change'));
            }
            
            clearSearch() {
                this.searchInput.value = '';
                this.clearButton.style.display = 'none';
                this.showAllProducts();
                this.searchInput.focus();
                
                // Reset active filter
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector('.filter-btn[data-category=""]').classList.add('active');
                this.activeFilter = '';
            }
            
            formatPrice(price) {
                return new Intl.NumberFormat('en-US').format(price);
            }
            
            showNotification(message, type = 'info') {
                // This will be implemented in the main controller
                if (window.salesForm) {
                    window.salesForm.showNotification(message, type);
                }
            }
            
            reset() {
                this.changeProduct();
                this.clearSearch();
                this.close();
            }
            
            getValue() {
                return this.hiddenSelect.value;
            }
            
            getSelectedData() {
                return this.selectedProductData;
            }
        }

        // Enhanced Sales Form Controller
        class SalesFormController {
            constructor() {
                this.form = document.getElementById('sales-form');
                this.productSelector = new EnhancedProductSelector();
                this.quantityInput = document.getElementById('quantity');
                this.quantityMinus = document.getElementById('quantity-minus');
                this.quantityPlus = document.getElementById('quantity-plus');
                this.unitPriceInput = document.getElementById('unit_price');
                this.useSuggestedBtn = document.getElementById('use-suggested-price');
                this.totalAmount = document.getElementById('total-amount');
                this.totalBreakdown = document.getElementById('total-breakdown');
                this.stockWarning = document.getElementById('stock-warning');
                this.suggestedPrice = document.getElementById('suggested-price');
                this.suggestedPriceValue = document.getElementById('suggested-price-value');
                this.submitBtn = document.getElementById('submit-btn');
                this.resetBtn = document.getElementById('reset-btn');
                
                this.init();
            }
            
            init() {
                this.bindEvents();
                this.setupKeyboardShortcuts();
                this.updateSubmitButton();
                
                // Make the controller globally available
                window.salesForm = this;
            }
            
            bindEvents() {
                // Product selection change
                document.getElementById('product_id').addEventListener('change', () => {
                    this.updateProductInfo();
                });
                
                // Quantity controls
                this.quantityMinus.addEventListener('click', () => this.adjustQuantity(-1));
                this.quantityPlus.addEventListener('click', () => this.adjustQuantity(1));
                this.quantityInput.addEventListener('input', () => this.handleQuantityChange());
                this.quantityInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.unitPriceInput.focus();
                    }
                });
                
                // Price input
                this.unitPriceInput.addEventListener('input', () => this.handlePriceChange());
                this.unitPriceInput.addEventListener('keydown', (e) => {
                    this.handlePriceKeyDown(e);
                });
                this.useSuggestedBtn.addEventListener('click', () => this.useSuggestedPrice());
                
                // Payment method selection
                document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                    radio.addEventListener('change', () => this.updateSubmitButton());
                });
                
                // Form submission
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
                
                // Reset button
                this.resetBtn.addEventListener('click', () => this.resetForm());
            }
            
            setupKeyboardShortcuts() {
                document.addEventListener('keydown', (e) => {
                    // Only handle shortcuts if not typing in an input
                    if (e.target.tagName === 'INPUT' && e.target.type === 'text') return;
                    
                    if (e.key === 'F2') {
                        e.preventDefault();
                        document.getElementById('product-search').focus();
                    }
                    if (e.key === 'F3') {
                        e.preventDefault();
                        this.quantityInput.focus();
                    }
                    if (e.key === 'F4') {
                        e.preventDefault();
                        this.unitPriceInput.focus();
                    }
                    if (e.ctrlKey && e.key === 'r') {
                        e.preventDefault();
                        this.resetForm();
                    }
                });
            }
            
            updateProductInfo() {
                const selectedData = this.productSelector.getSelectedData();
                
                if (selectedData) {
                    // Show suggested price
                    this.suggestedPriceValue.textContent = this.formatPrice(selectedData.price) + ' TZS';
                    this.suggestedPrice.classList.add('show');
                    
                    // Auto-fill price if empty
                    if (!this.unitPriceInput.value.trim()) {
                        this.unitPriceInput.value = this.formatPrice(selectedData.price);
                        this.handlePriceChange();
                    }
                    
                    // Update quantity constraints
                    this.quantityInput.setAttribute('max', selectedData.stock);
                    this.quantityPlus.disabled = false;
                    
                    // Validate current quantity
                    const currentQuantity = parseInt(this.quantityInput.value) || 0;
                    if (currentQuantity > selectedData.stock) {
                        this.quantityInput.value = selectedData.stock;
                        this.showNotification(`Quantity adjusted to maximum available: ${selectedData.stock}`, 'warning');
                    }
                    
                    this.updateStockWarning();
                } else {
                    this.suggestedPrice.classList.remove('show');
                    this.stockWarning.classList.remove('show');
                    this.quantityInput.removeAttribute('max');
                    this.quantityPlus.disabled = true;
                }
                
                this.calculateTotal();
                this.updateSubmitButton();
            }
            
            adjustQuantity(delta) {
                const selectedData = this.productSelector.getSelectedData();
                if (!selectedData) return;
                
                const currentQuantity = parseInt(this.quantityInput.value) || 0;
                const newQuantity = Math.max(1, Math.min(selectedData.stock, currentQuantity + delta));
                
                this.quantityInput.value = newQuantity;
                this.handleQuantityChange();
            }
            
            handleQuantityChange() {
                const selectedData = this.productSelector.getSelectedData();
                const quantity = parseInt(this.quantityInput.value) || 0;
                
                // Update quantity buttons
                this.quantityMinus.disabled = quantity <= 1;
                
                if (selectedData) {
                    this.quantityPlus.disabled = quantity >= selectedData.stock;
                    
                    // Validate against stock
                    if (quantity > selectedData.stock) {
                        this.quantityInput.value = selectedData.stock;
                        this.showNotification(`Maximum available: ${selectedData.stock}`, 'warning');
                    }
                    
                    this.updateStockWarning();
                }
                
                this.calculateTotal();
                this.updateSubmitButton();
            }
            
            handlePriceChange() {
                // Format price with commas
                this.formatPriceInput();
                this.calculateTotal();
                this.updateSubmitButton();
            }
            
            handlePriceKeyDown(e) {
                // Allow navigation keys
                const allowedKeys = [
                    'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                    'Home', 'End', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'
                ];
                
                if (allowedKeys.includes(e.key)) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('lipa').focus();
                    }
                    return;
                }
                
                // Allow Ctrl combinations
                if (e.ctrlKey && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) {
                    return;
                }
                
                // Allow numbers and decimal point
                if (!/[\d.]/.test(e.key)) {
                    e.preventDefault();
                }
            }
            
            formatPriceInput() {
                const value = this.unitPriceInput.value.replace(/[^\d.]/g, '');
                const numericValue = parseFloat(value) || 0;
                
                // Format with commas
                const formattedValue = this.formatPrice(numericValue);
                
                // Only update if the formatted value is different
                if (this.unitPriceInput.value !== formattedValue) {
                    const cursorPos = this.unitPriceInput.selectionStart;
                    this.unitPriceInput.value = formattedValue;
                    
                    // Try to maintain cursor position
                    const newCursorPos = Math.min(cursorPos, formattedValue.length);
                    this.unitPriceInput.setSelectionRange(newCursorPos, newCursorPos);
                }
            }
            
            useSuggestedPrice() {
                const selectedData = this.productSelector.getSelectedData();
                if (selectedData) {
                    this.unitPriceInput.value = this.formatPrice(selectedData.price);
                    this.calculateTotal();
                    this.updateSubmitButton();
                    
                    // Visual feedback
                    const originalText = this.useSuggestedBtn.textContent;
                    this.useSuggestedBtn.innerHTML = '<i class="fas fa-check"></i> Applied!';
                    this.useSuggestedBtn.style.background = 'var(--success-color)';
                    
                    setTimeout(() => {
                        this.useSuggestedBtn.textContent = originalText;
                        this.useSuggestedBtn.style.background = '';
                    }, 1500);
                }
            }
            
            updateStockWarning() {
                const selectedData = this.productSelector.getSelectedData();
                const quantity = parseInt(this.quantityInput.value) || 0;
                
                if (!selectedData) {
                    this.stockWarning.classList.remove('show');
                    return;
                }
                
                const stock = selectedData.stock;
                const warningText = this.stockWarning.querySelector('.warning-text');
                
                if (stock <= 0) {
                    this.stockWarning.classList.add('show');
                    warningText.textContent = 'Product is out of stock!';
                } else if (quantity > stock) {
                    this.stockWarning.classList.add('show');
                    warningText.textContent = `Only ${stock} units available`;
                } else if (stock <= 5) {
                    this.stockWarning.classList.add('show');
                    warningText.textContent = `Low stock: Only ${stock} units remaining`;
                } else {
                    this.stockWarning.classList.remove('show');
                }
            }
            
            calculateTotal() {
                const unitPrice = this.parsePrice(this.unitPriceInput.value);
                const quantity = parseInt(this.quantityInput.value) || 0;
                const total = unitPrice * quantity;
                
                // Update main total display
                this.totalAmount.textContent = this.formatPrice(total);
                
                // Update breakdown
                if (unitPrice > 0 && quantity > 0) {
                    document.getElementById('breakdown-unit-price').textContent = this.formatPrice(unitPrice) + ' TZS';
                    document.getElementById('breakdown-quantity').textContent = quantity;
                    document.getElementById('breakdown-total').textContent = this.formatPrice(total) + ' TZS';
                    this.totalBreakdown.classList.add('show');
                } else {
                    this.totalBreakdown.classList.remove('show');
                }
                
                // Visual feedback for total change
                if (total > 0) {
                    const totalDisplay = document.getElementById('total-display');
                    totalDisplay.style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        totalDisplay.style.transform = 'scale(1)';
                    }, 200);
                }
            }
            
            updateSubmitButton() {
                const hasProduct = this.productSelector.getValue() !== '';
                const hasQuantity = this.quantityInput.value !== '' && parseInt(this.quantityInput.value) > 0;
                const hasUnitPrice = this.unitPriceInput.value !== '' && this.parsePrice(this.unitPriceInput.value) > 0;
                const hasPaymentMethod = document.querySelector('input[name="payment_method"]:checked') !== null;
                
                const isValid = hasProduct && hasQuantity && hasUnitPrice && hasPaymentMethod;
                
                this.submitBtn.disabled = !isValid;
                
                if (isValid) {
                    this.submitBtn.classList.add('ready');
                } else {
                    this.submitBtn.classList.remove('ready');
                }
            }
            
            validateForm() {
                const selectedData = this.productSelector.getSelectedData();
                const quantity = parseInt(this.quantityInput.value) || 0;
                const unitPrice = this.parsePrice(this.unitPriceInput.value);
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                
                if (!selectedData) {
                    this.showNotification('Please select a product', 'error');
                    return false;
                }
                
                if (quantity <= 0) {
                    this.showNotification('Please enter a valid quantity', 'error');
                    this.quantityInput.focus();
                    return false;
                }
                
                if (unitPrice <= 0) {
                    this.showNotification('Please enter a valid unit price', 'error');
                    this.unitPriceInput.focus();
                    return false;
                }
                
                if (!paymentMethod) {
                    this.showNotification('Please select a payment method', 'error');
                    return false;
                }
                
                if (quantity > selectedData.stock) {
                    this.showNotification(`Not enough stock. Available: ${selectedData.stock}`, 'error');
                    this.quantityInput.focus();
                    return false;
                }
                
                return true;
            }
            
            handleSubmit(e) {
                e.preventDefault();
                
                if (!this.validateForm()) {
                    return;
                }
                
                if (!this.confirmSale()) {
                    return;
                }
                
                // Show loading state
                this.submitBtn.innerHTML = '<span class="loading"></span> Processing Sale...';
                this.submitBtn.disabled = true;
                
                // Clean price for submission
                const cleanPrice = this.parsePrice(this.unitPriceInput.value);
                
                // Create a temporary input with clean price
                const tempInput = document.createElement('input');
                tempInput.type = 'hidden';
                tempInput.name = 'unit_price_clean';
                tempInput.value = cleanPrice.toString();
                this.form.appendChild(tempInput);
                
                // Submit form (in real implementation, this would submit to server)
                this.simulateFormSubmission();
            }
            
            simulateFormSubmission() {
                // Simulate form submission delay
                setTimeout(() => {
                    const selectedData = this.productSelector.getSelectedData();
                    const quantity = parseInt(this.quantityInput.value);
                    const unitPrice = this.parsePrice(this.unitPriceInput.value);
                    const total = unitPrice * quantity;
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                    
                    // Show success message
                    const successMessage = `Sale completed successfully! 
                        Product: ${selectedData.name}, 
                        Quantity: ${quantity}, 
                        Unit Price: ${this.formatPrice(unitPrice)} TZS, 
                        Total: ${this.formatPrice(total)} TZS, 
                        Payment: ${paymentMethod}`;
                    
                    this.showNotification('Sale completed successfully!', 'success');
                    
                    // Update stats (simulate)
                    this.updateStats(total, quantity);
                    
                    // Reset form
                    setTimeout(() => {
                        this.resetForm();
                    }, 2000);
                    
                    // Reset submit button
                    this.submitBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Complete Sale';
                    this.submitBtn.disabled = false;
                }, 2000);
            }
            
            updateStats(saleAmount, itemCount) {
                // Update today's stats (simulate)
                const totalSalesEl = document.getElementById('total-sales');
                const totalRevenueEl = document.getElementById('total-revenue');
                const itemsSoldEl = document.getElementById('items-sold');
                
                if (totalSalesEl) {
                    const currentSales = parseInt(totalSalesEl.textContent) || 0;
                    totalSalesEl.textContent = currentSales + 1;
                }
                
                if (totalRevenueEl) {
                    const currentRevenue = parseInt(totalRevenueEl.textContent.replace(/,/g, '')) || 0;
                    totalRevenueEl.textContent = this.formatPrice(currentRevenue + saleAmount);
                }
                
                if (itemsSoldEl) {
                    const currentItems = parseInt(itemsSoldEl.textContent) || 0;
                    itemsSoldEl.textContent = currentItems + itemCount;
                }
            }
            
            confirmSale() {
                const selectedData = this.productSelector.getSelectedData();
                const quantity = parseInt(this.quantityInput.value);
                const unitPrice = this.parsePrice(this.unitPriceInput.value);
                const total = unitPrice * quantity;
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                
                return confirm(`🛒 Confirm Sale Details:

📦 Product: ${selectedData.name}
🏷️ Brand: ${selectedData.brand}
📏 Size: ${selectedData.size}
🎨 Color: ${selectedData.color}

📊 Quantity: ${quantity}
💰 Unit Price: ${this.formatPrice(unitPrice)} TZS
💳 Payment: ${paymentMethod}

🧾 Total Amount: ${this.formatPrice(total)} TZS

✅ Proceed with this sale?`);
            }
            
            resetForm() {
                this.productSelector.reset();
                this.quantityInput.value = '';
                this.unitPriceInput.value = '';
                this.totalAmount.textContent = '0';
                this.totalBreakdown.classList.remove('show');
                this.stockWarning.classList.remove('show');
                this.suggestedPrice.classList.remove('show');
                
                // Reset quantity buttons
                this.quantityMinus.disabled = true;
                this.quantityPlus.disabled = true;
                
                // Reset payment methods
                document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                    radio.checked = false;
                });
                
                this.updateSubmitButton();
                
                // Focus product search
                setTimeout(() => {
                    document.getElementById('product-search').focus();
                }, 300);
                
                this.showNotification('Form reset successfully', 'success');
            }
            
            formatPrice(price) {
                return new Intl.NumberFormat('en-US').format(Math.round(price || 0));
            }
            
            parsePrice(priceString) {
                return parseFloat(priceString?.replace(/,/g, '') || '0') || 0;
            }
            
            showNotification(message, type = 'info') {
                this.createToast(message, type);
            }
            
            createToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                
                const iconMap = {
                    success: 'check-circle',
                    error: 'exclamation-circle',
                    warning: 'exclamation-triangle',
                    info: 'info-circle'
                };
                
                toast.innerHTML = `
                    <div class="toast-icon">
                        <i class="fas fa-${iconMap[type] || 'info-circle'}"></i>
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${this.getTitleForType(type)}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close" aria-label="Close notification">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                const container = document.getElementById('toast-container') || document.body;
                container.appendChild(toast);
                
                // Show toast
                setTimeout(() => {
                    toast.classList.add('show');
                }, 100);
                
                // Auto-hide toast
                const autoHideTimeout = setTimeout(() => {
                    this.removeToast(toast);
                }, type === 'error' ? 6000 : 4000);
                
                // Manual close
                const closeBtn = toast.querySelector('.toast-close');
                closeBtn.addEventListener('click', () => {
                    clearTimeout(autoHideTimeout);
                    this.removeToast(toast);
                });
                
                // Click to close
                toast.addEventListener('click', () => {
                    clearTimeout(autoHideTimeout);
                    this.removeToast(toast);
                });
            }
            
            removeToast(toast) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
            
            getTitleForType(type) {
                const titles = {
                    success: 'Success',
                    error: 'Error',
                    warning: 'Warning',
                    info: 'Information'
                };
                return titles[type] || 'Notification';
            }
        }

        // Toast Container Creation
        function createToastContainer() {
            if (!document.getElementById('toast-container')) {
                const container = document.createElement('div');
                container.id = 'toast-container';
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                    pointer-events: none;
                `;
                document.body.appendChild(container);
            }
        }

        // Global function for clearing search
        function clearSearch() {
            const searchInput = document.getElementById('product-search');
            if (searchInput) {
                searchInput.value = '';
                document.getElementById('clear-search').click();
            }
        }

        // Initialize on DOM content loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Create toast container
            createToastContainer();
            
            // Initialize the sales form controller
            new SalesFormController();
            
            // Add ready animation styles
            const style = document.createElement('style');
            style.textContent = `
                .btn.ready {
                    animation: readyPulse 2s infinite;
                }
                
                @keyframes readyPulse {
                    0%, 100% { 
                        box-shadow: var(--shadow-md);
                    }
                    50% { 
                        box-shadow: var(--shadow-lg);
                        transform: translateY(-1px);
                    }
                }
                
                .toast {
                    pointer-events: auto;
                }
                
                .toast.show {
                    animation: toastSlideIn 0.3s ease;
                }
                
                @keyframes toastSlideIn {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                
                /* Smooth scrolling for focus */
                .products-container {
                    scroll-behavior: smooth;
                }
                
                /* Touch improvements for mobile */
                @media (hover: none) and (pointer: coarse) {
                    .product-item {
                        min-height: 100px;
                        padding: 1.5rem 1rem;
                    }
                    
                    .btn {
                        min-height: 60px;
                        padding: 1.25rem 2rem;
                        font-size: 1.1rem;
                    }
                    
                    .filter-btn {
                        padding: 1rem 0.75rem;
                        font-size: 0.9rem;
                    }
                    
                    .payment-label {
                        padding: 1.25rem;
                    }
                    
                    .quantity-btn {
                        width: 60px;
                        height: 60px;
                        font-size: 1.2rem;
                    }
                    
                    .search-btn {
                        width: 48px;
                        height: 48px;
                    }
                }
                
                /* High contrast mode improvements */
                @media (prefers-contrast: high) {
                    .product-item:hover,
                    .product-item.focused {
                        outline: 3px solid var(--primary-color);
                    }
                    
                    .btn:focus,
                    .form-control:focus {
                        outline: 3px solid var(--primary-color);
                        outline-offset: 2px;
                    }
                }
                
                /* Reduced motion preferences */
                @media (prefers-reduced-motion: reduce) {
                    .dropdown-panel,
                    .selected-product,
                    .suggested-price,
                    .total-breakdown {
                        animation: none !important;
                        transition: none !important;
                    }
                    
                    .btn:hover,
                    .product-item:hover {
                        transform: none !important;
                    }
                }
            `;
            document.head.appendChild(style);
            
            console.log('🚀 Enhanced Mobile-First Make Sale system initialized successfully!');
        });

        // Service Worker Registration for Offline Support (Optional)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                // Uncomment the following lines to enable service worker
                // navigator.serviceWorker.register('/sw.js')
                //     .then(function(registration) {
                //         console.log('SW registered: ', registration);
                //     })
                //     .catch(function(registrationError) {
                //         console.log('SW registration failed: ', registrationError);
                //     });
            });
        }
    </script>
</body>
</html>