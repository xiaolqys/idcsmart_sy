<?php
/**
 * 魔方财务上游检测工具
 * 查询产品对应的上游购买链接
 */

error_reporting(0);

$action = isset($_GET['action']) ? $_GET['action'] : 'page';
$targetUrl = isset($_GET['url']) ? $_GET['url'] : '';
$productIds = isset($_GET['ids']) ? $_GET['ids'] : '';

// API请求模式
if ($action === 'api') {
    header('Content-Type: application/json; charset=utf-8');
    
    if (empty($targetUrl) || empty($productIds)) {
        echo json_encode(['error' => '缺少参数']);
        exit;
    }
    
    $targetUrl = rtrim(trim($targetUrl), '/');
    if (!preg_match('/^https?:\/\//', $targetUrl)) {
        $targetUrl = 'https://' . $targetUrl;
    }
    
    $ids = array_filter(array_map('trim', explode(',', $productIds)), 'is_numeric');
    if (empty($ids)) {
        echo json_encode(['error' => '产品ID格式错误']);
        exit;
    }
    
    // 构建请求
    $apiUrl = $targetUrl . '/api/product/prodetail';
    $queryParams = [];
    foreach ($ids as $idx => $pid) {
        $queryParams['pids[' . $idx . ']'] = $pid;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . http_build_query($queryParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    $detailMap = isset($data['data']['detail']) ? $data['data']['detail'] : [];
    
    $results = [];
    foreach ($ids as $pid) {
        $pidStr = (string)$pid;
        if (isset($detailMap[$pidStr])) {
            $upstreamUrl = isset($detailMap[$pidStr]['upstream_product_shopping_url']) 
                ? $detailMap[$pidStr]['upstream_product_shopping_url'] : null;
            $results[$pid] = [
                'has_upstream' => !empty($upstreamUrl),
                'upstream_url' => $upstreamUrl
            ];
        } else {
            $results[$pid] = ['has_upstream' => false, 'upstream_url' => null];
        }
    }
    
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上游链接检测 - 功能合集</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Microsoft YaHei", sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 600px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }
        
        .title {
            font-size: 18px;
            font-weight: 500;
            color: #333;
            margin-bottom: 24px;
        }
        
        .title span {
            color: #007bff;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .form-label .required {
            color: #dc3545;
            margin-right: 4px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }
        
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .submit-btn:hover {
            background-color: #0056b3;
        }
        
        .result-container {
            margin-top: 20px;
            display: none;
        }
        
        .result-header {
            font-size: 16px;
            font-weight: 500;
            color: #333;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .result-content {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .result-item {
            padding: 12px 16px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: all 0.2s;
        }
        
        .result-item:hover {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        .product-id {
            font-weight: bold;
            color: #333;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-found {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-notfound {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .upstream-link {
            display: block;
            font-size: 12px;
            color: #0066cc;
            text-decoration: none;
            word-break: break-all;
            margin-top: 8px;
        }
        
        .upstream-link:hover {
            text-decoration: underline;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-size: 13px;
            padding: 20px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 13px;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 20px;
        }
        
        .footer-note {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">智简魔方财务系统上游链接检测 <span></span></div>
        
        <form id="detectForm">
            <div class="form-group">      
                <label class="form-label">
                    <span class="required">*</span>网站地址
                </label>
                <input 
                    type="text" 
                    class="form-input" 
                    id="targetUrl"
                    placeholder="https://example.com" 
                    value="https://example.com"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <span class="required">*</span>产品ID
                </label>
                <input 
                    type="text" 
                    class="form-input" 
                    id="productIds"
                    placeholder="多个ID用逗号分隔，如：1,2,3" 
                    value="1"
                >
            </div>
            
            <button type="submit" class="submit-btn">查询上游链接</button>
        </form>
        
        <div class="result-container" id="resultContainer">
            <div class="result-header">查询结果</div>
            <div class="result-content" id="resultContent"></div>
        </div>
        
        <div class="footer-note">
            仅供学习交流使用，请勿用于非法用途。联系邮箱：xiaolqy (法定假日会回复）</br>
            --若查不到请到github.com/xiaolqys 下载源码</br>@Powered 欢凝文化
        </div>
    </div>

    <script>
        document.getElementById('detectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const targetUrl = document.getElementById('targetUrl').value.trim();
            const productIds = document.getElementById('productIds').value.trim();
            const resultContainer = document.getElementById('resultContainer');
            const resultContent = document.getElementById('resultContent');
            
            if (!targetUrl || !productIds) {
                resultContainer.style.display = 'block';
                resultContent.innerHTML = '<div class="error">请填写网站地址和产品ID</div>';
                return;
            }
            
            // 显示加载状态
            resultContainer.style.display = 'block';
            resultContent.innerHTML = '<div class="loading">正在查询中，请稍候...</div>';
            
            // 发送API请求
            fetch(`?action=api&url=${encodeURIComponent(targetUrl)}&ids=${encodeURIComponent(productIds)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        resultContent.innerHTML = `<div class="error">错误：${data.error}</div>`;
                        return;
                    }
                    
                    if (!data.success || !data.results) {
                        resultContent.innerHTML = '<div class="error">查询失败，请检查网站地址是否正确</div>';
                        return;
                    }
                    
                    displayResults(data.results);
                })
                .catch(error => {
                    resultContent.innerHTML = `<div class="error">网络错误：${error.message}</div>`;
                });
        });
        
        function displayResults(results) {
            const resultContent = document.getElementById('resultContent');
            const resultArray = Object.entries(results);
            
            if (resultArray.length === 0) {
                resultContent.innerHTML = '<div class="no-data">没有查询到数据</div>';
                return;
            }
            
            let html = '';
            resultArray.forEach(([pid, result]) => {
                const hasUpstream = result.has_upstream;
                const upstreamUrl = result.upstream_url;
                
                html += `
                    <div class="result-item">
                        <div class="product-id">产品ID: ${pid}</div>
                        <span class="status ${hasUpstream ? 'status-found' : 'status-notfound'}">
                            ${hasUpstream ? '✅ 找到上游链接' : '❌ 未找到上游链接'}
                        </span>
                        ${hasUpstream ? `<a href="${upstreamUrl}" target="_blank" class="upstream-link">${upstreamUrl}</a>` : '<div style="font-size: 12px; color: #666; margin-top: 8px;">该产品没有上游链接</div>'}
                    </div>
                `;
            });
            
            resultContent.innerHTML = html;
        }
        
        // 示例URL点击填充
        document.getElementById('targetUrl').addEventListener('click', function(e) {
            if (this.value === 'https://example.com') {
                this.value = '';
            }
        });
        
        // 示例ID点击填充
        document.getElementById('productIds').addEventListener('click', function(e) {
            if (this.value === '1') {
                this.value = '';
            }
        });
    </script>
</body>
</html>
