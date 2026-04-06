# 🔍 上游信息检测

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.0%2B-777BB4.svg)](https://php.net)

> 魔方财务系统上游信息检测工具 - 快速检测 `/api/product/prodetail` 接口是否泄露上游购买链接

## 📖 项目背景（下载php放到网站部署即可）

魔方财务系统（ZJMF）存在一个安全漏洞：`/api/product/prodetail` 接口会返回 `upstream_product_shopping_url` 等上游敏感信息。本工具可快速检测目标站点是否存在该问题。

## ✨ 功能特点

- 🚀 **API原理** - https://你的域名/api/product/prodetail?pids[0]=产品ID  （访问后查找字符：upstream_product_shopping_url）


## 🔧 联系我：xiaolqy@qq.com (中国大陆法定节假日回复）-请勿用于非法用途，产生原因与我方无关

## 修复请访问：https://github.com/FuRuiORG/ZJMF-noupstream
