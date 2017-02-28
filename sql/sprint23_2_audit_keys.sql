DROP TABLE IF EXISTS `audit_keys`;


-- phpMyAdmin SQL Dump
-- version 4.4.13.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 14, 2016 at 06:08 PM
-- Server version: 5.6.30-0ubuntu0.15.10.1
-- PHP Version: 5.6.11-1ubuntu3.4


-- SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `karma`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_keys`
--

CREATE TABLE IF NOT EXISTS `audit_keys` (
  `id` bigint(20) NOT NULL,
  `backend_key` varchar(255) DEFAULT NULL,
  `ui_key` varchar(255) DEFAULT NULL,
  `entity` varchar(255) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `audit_keys`
--

INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(1, 'description', 'Statement_of_intent', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(5, 'name', 'NPO_Name', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(6, 'address1', 'Address_Line_1', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(7, 'address2', 'Address_Line_2', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(8, 'city', 'City', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(9, 'state', 'State', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(10, 'zip', 'Zip', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(11, 'country', 'Country', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(12, 'websiteUrl', 'Website_Url', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(13, 'donationStatus', 'Donation_Status', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(14, 'donationUrl', 'Donation_Url', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(15, 'brandingUrl', 'Branding_Url', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(17, 'copyright', 'Copyright', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(18, 'contactUs', 'Contact_Us', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(19, 'category', 'Pillars', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(20, 'handles', 'Twitter_Handles', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(21, 'facebookHandles', 'Facebook_URL', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(22, 'targetAmount', 'Funding', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(23, 'totalBenefeciaries', 'Target_Benefeciaries', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(24, 'status', 'Status', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(25, 'startDate', 'Start_Date', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(26, 'endDate', 'End_Date', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(27, 'title', 'Name', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(28, 'shortDescription', 'Short_Description', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(29, 'longDescription', 'Project_Background', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(30, 'countryRegion', 'Country_Regions', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(31, 'outcomes', 'Outcomes', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(32, 'imageUrl', 'Image_Url', 'project');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(33, 'status', 'Status', 'project status');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(34, 'goal', 'Title', 'outcome');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(35, 'description', 'Description', 'outcome');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(36, 'goal_target', 'Goal', 'outcome');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(37, 'goal_achieved', 'Current', 'outcome');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(38, 'projectReportType', 'Update_Type', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(39, 'projectName', 'Project', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(40, 'outcomeName', 'Outcome', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(41, 'goalOutcome', 'Goal', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(42, 'currentOutcome', 'Current', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(43, 'title', 'Update', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(44, 'image_urls', 'Images', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(45, 'video_urls', 'Videos', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(47, 'url', 'url', 'activity video');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(48, 'thumb_url', 'thumb_url', 'activity video');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(50, 'url', 'url', 'activity image');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(51, 'thumb_url', 'thumb_url', 'activity image');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(53, 'name', 'Name', 'donor');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(54, 'donorUrl', 'Donor_Url', 'donor');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(55, 'imageUrl', 'Image_Url', 'donor');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(56, 'project', 'Project', 'donor');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(57, 'caption', 'caption', 'activity video');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(58, 'thumbUrl', 'thumb_url', 'activity video');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(59, 'caption', 'caption', 'activity image');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(60, 'thumbUrl', 'thumb_url', 'activity image');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(61, 'status', 'status', 'member status');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(62, 'thumbUrl', 'thumb_url', 'activity video caption');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(63, 'caption', 'caption', 'activity video caption');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(64, 'url', 'url', 'activity video caption');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(65, 'url', 'url', 'activity image caption');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(66, 'caption', 'caption', 'activity image caption');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(67, 'thumbUrl', 'thumb_url', 'activity image caption');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(68, 'outcome', 'Title', 'outcomes');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(70, 'currentOutcome', 'Current', 'outcomes');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(71, 'goalOutcome', 'Goal', 'outcomes');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(72, 'description', 'Description', 'outcomes');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(73, 'categoryname', 'Pillar', 'outcomes');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(74, 'category', 'Pillar', 'category');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(75, 'handleName', 'Handle', 'handles');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(76, 'handleName', 'URL', 'facebookHandles');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(77, 'country', 'country', 'countryRegion');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(78, 'state', 'state', 'countryRegion');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(79, 'url', 'url', 'imageUrls');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(80, 'caption', 'caption', 'imageUrls');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(81, 'thumb_url', 'thumb_url', 'imageUrls');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(82, 'url', 'url', 'videoUrls');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(83, 'caption', 'caption', 'videoUrls');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(84, 'thumb_url', 'thumb_url', 'videoUrls');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(86, 'imageUrl', 'Logo', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(87, 'faviconUrl', 'Icon', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(94, 'imageUrls', 'image_urls', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(95, 'videoUrls', 'video_urls', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(96, 'thumbUrl', 'thumb_url', 'imageUrls');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(97, 'thumbUrl', 'thumb_url', 'videoUrls');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(98, 'categoryname', 'Pillar', 'outcome');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(99, 'newProgress', 'New_Progress', 'activity');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(100, 'programsNetSpend', 'Spend_Programs', 'NPO profile');
INSERT INTO `audit_keys` (`id`, `backend_key`, `ui_key`, `entity`) VALUES(101, 'fundingsToDate', 'To_Date_Amount', 'project');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_keys`
--
ALTER TABLE `audit_keys`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_keys`
--
ALTER TABLE `audit_keys`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=102;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;