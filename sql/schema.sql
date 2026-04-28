CREATE DATABASE IF NOT EXISTS AutoSpares;
USE AutoSpares;

CREATE TABLE IF NOT EXISTS Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Role ENUM('Admin', 'Customer') NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    PhoneNumber VARCHAR(15),
    Address VARCHAR(255),
City VARCHAR(50),
Country VARCHAR(50),
Password VARCHAR(255) NOT NULL,
CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


create table if not exists Categories (
    id int auto_increment primary key,
    name varchar(50) not null unique,
    description text,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp
);


create table if not exists sub_categories (
    id int auto_increment primary key,
    category_id int not null,
    name varchar(50) not null unique,
    description text,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp,
    foreign key (category_id) references Categories(id) on delete cascade
);

create table if not exists brands (
    id int auto_increment primary key,
    name varchar(50) not null unique,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp
);

create table if not exists models (
    id int auto_increment primary key,
    brand_id int not null,
    name varchar(50) not null unique,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp,
    foreign key (brand_id) references brands(id) on delete cascade
);


create table if not exists products (
    id int auto_increment primary key,
    sub_category_id int not null,
    model_id int not null,
    name varchar(100) not null,
    description text,
    price decimal(10, 2) not null,
    stock_quantity int not null,
    img varchar(255),
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp,
    foreign key (sub_category_id) references sub_categories(id) on delete cascade,
    foreign key (model_id) references models(id) on delete cascade
);


create table if not exists orders (
    id int auto_increment primary key,
    user_id int not null,
    customer_name varchar(100) not null,
    customer_phone_number varchar(15) not null,
    customer_address varchar(255) not null,
    total_amount decimal(10, 2) not null,
    status enum('Pending', 'Completed', 'Canceled', 'Failed') default 'Pending',
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp,
    foreign key (user_id) references Users(id) on delete cascade
);


create table if not exists order_items (
    id int auto_increment primary key,
    order_id int not null,
    product_id int not null,
    quantity int not null,
    price decimal(10, 2) not null,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp,
    foreign key (order_id) references orders(id) on delete cascade,
    foreign key (product_id) references products(id) on delete cascade
);


create table if not exists payments (
    id int auto_increment primary key,
    order_id int not null,
    payment_method enum('Credit Card', 'PayNow', 'Bank Transfer') not null,
    payment_status enum('Pending', 'Completed', 'Failed') default 'Pending',
    merchant_reference varchar(100),
    paynow_reference varchar(100),
    poll_url varchar(255),
    browser_url varchar(255),
    payment_details text,
    paid_at timestamp null,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp,
    foreign key (order_id) references orders(id) on delete cascade
);
