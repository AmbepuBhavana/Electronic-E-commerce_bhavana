-- Create Group Buy Procedure
DROP PROCEDURE IF EXISTS create_group_buy;
DELIMITER //

CREATE PROCEDURE create_group_buy(
    IN p_product_id INT,
    IN p_creator_user_id INT,
    IN p_title VARCHAR(255),
    IN p_description TEXT,
    IN p_min_participants INT,
    IN p_max_participants INT,
    IN p_discount_type ENUM('percentage', 'fixed'),
    IN p_discount_value DECIMAL(10,2),
    IN p_end_datetime DATETIME,
    OUT p_group_buy_id INT
)
BEGIN
    DECLARE v_base_price DECIMAL(10,2);
    DECLARE v_group_price DECIMAL(10,2);
    DECLARE v_product_exists INT;
    DECLARE v_user_exists INT;

    -- Start transaction
    START TRANSACTION;

    -- Validate product exists
    SELECT COUNT(*) INTO v_product_exists 
    FROM products 
    WHERE id = p_product_id;

    IF v_product_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Product does not exist';
    END IF;

    -- Validate user exists
    SELECT COUNT(*) INTO v_user_exists 
    FROM users 
    WHERE id = p_creator_user_id;

    IF v_user_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'User does not exist';
    END IF;

    -- Get base product price
    SELECT price INTO v_base_price 
    FROM products 
    WHERE id = p_product_id;

    -- Calculate group price based on discount type
    IF p_discount_type = 'percentage' THEN
        SET v_group_price = v_base_price * (1 - (p_discount_value / 100));
    ELSE
        SET v_group_price = v_base_price - p_discount_value;
    END IF;

    -- Validate group price
    IF v_group_price <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Calculated group price must be greater than zero';
    END IF;

    -- Insert new group buy
    INSERT INTO group_buy (
        product_id, creator_user_id, title, description, 
        base_price, min_participants, max_participants, 
        current_participants, discount_type, discount_value, 
        group_price, end_datetime, status
    ) VALUES (
        p_product_id, p_creator_user_id, p_title, p_description,
        v_base_price, p_min_participants, p_max_participants,
        0, p_discount_type, p_discount_value, 
        v_group_price, p_end_datetime, 'active'
    );

    -- Get the last inserted group buy ID
    SET p_group_buy_id = LAST_INSERT_ID();

    -- Log transaction
    INSERT INTO group_buy_transactions 
    (group_buy_id, user_id, transaction_type, status, transaction_datetime) 
    VALUES 
    (p_group_buy_id, p_creator_user_id, 'create', 'success', NOW());

    -- Commit transaction
    COMMIT;
END //

-- Enhanced Join Group Buy Procedure
DROP PROCEDURE IF EXISTS join_group_buy //

CREATE PROCEDURE join_group_buy(
    IN p_group_buy_id INT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_group_buy_exists INT;
    DECLARE v_current_participants INT;
    DECLARE v_max_participants INT;
    DECLARE v_status ENUM('pending', 'active', 'successful', 'failed', 'canceled');
    DECLARE v_user_already_joined INT;
    DECLARE v_min_participants INT;

    -- Start transaction
    START TRANSACTION;

    -- Check if group buy exists and is active
    SELECT COUNT(*), current_participants, max_participants, status, min_participants 
    INTO v_group_buy_exists, v_current_participants, v_max_participants, v_status, v_min_participants
    FROM group_buy 
    WHERE group_buy_id = p_group_buy_id;

    -- Validate group buy existence
    IF v_group_buy_exists = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Group buy does not exist';
    END IF;

    -- Check group buy status
    IF v_status != 'active' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Group buy is not currently active';
    END IF;

    -- Check if group buy is full
    IF v_current_participants >= v_max_participants THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Group buy has reached maximum participants';
    END IF;

    -- Check if user has already joined
    SELECT COUNT(*) INTO v_user_already_joined
    FROM group_buy_participants
    WHERE group_buy_id = p_group_buy_id AND user_id = p_user_id;

    IF v_user_already_joined > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'User has already joined this group buy';
    END IF;

    -- Insert participant
    INSERT INTO group_buy_participants 
    (group_buy_id, user_id, status) 
    VALUES 
    (p_group_buy_id, p_user_id, 'pending');

    -- Update current participants count
    UPDATE group_buy 
    SET current_participants = current_participants + 1 
    WHERE group_buy_id = p_group_buy_id;

    -- Log transaction
    INSERT INTO group_buy_transactions 
    (group_buy_id, user_id, transaction_type, status) 
    VALUES 
    (p_group_buy_id, p_user_id, 'join', 'pending');

    -- Check if group buy is now successful
    IF v_current_participants + 1 >= v_min_participants THEN
        UPDATE group_buy 
        SET status = 'successful' 
        WHERE group_buy_id = p_group_buy_id;
    END IF;

    -- Commit transaction
    COMMIT;
END //

DELIMITER ;
