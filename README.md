# Group Buy Feature for E-Commerce Platform

## Overview
This project implements a Group Buy feature that allows users to participate in collective purchases with potential discounts.

## Key Components
- `config/group_buy_procedures.sql`: Stored procedures for group buy operations
- `config/group_buy_table_setup.sql`: Database table definitions
- `add_group_buy_products.php`: Script to add sample products
- `create_multiple_group_buys.php`: Script to create multiple group buys
- `test_group_buy_workflow.php`: Comprehensive test script for group buy functionality

## Database Setup
1. Run `config/group_buy_table_setup.sql` to create necessary tables
2. Run `config/group_buy_procedures.sql` to create stored procedures

## Testing
1. Add products using `add_group_buy_products.php`
2. Create group buys using `create_multiple_group_buys.php`
3. Test workflow using `test_group_buy_workflow.php`

## Features
- Create group buys with minimum and maximum participants
- Apply percentage or fixed discounts
- Track group buy status
- Log group buy transactions

## Requirements
- PHP 7.4+
- MySQL/MariaDB
- PDO Extension
