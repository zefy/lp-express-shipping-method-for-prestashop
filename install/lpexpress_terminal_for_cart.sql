CREATE TABLE IF NOT EXISTS `PREFIX_lpexpress_terminal_for_cart` (
  `id_lpexpress_terminal_for_cart` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_cart` int(10) UNSIGNED NOT NULL,
  `id_terminal` int(10) NOT NULL,
  PRIMARY KEY (`id_lpexpress_terminal_for_cart`),
  UNIQUE KEY `id_cart` (`id_cart`)
) ENGINE=_ENGINE_ DEFAULT CHARSET=utf8;