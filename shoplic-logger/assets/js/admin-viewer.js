jQuery(document).ready(function($) {
    // 원본 컨텐츠 저장
    $(".sl-log-content").each(function() {
        $(this).data("original-content", $(this).html());
    });
    
    // 태그 필터 드롭다운 변경 이벤트
    $(document).on("change", ".sl-tag-filter-select", function() {
        var selectedTag = $(this).val();
        var card = $(this).closest(".sl-log-card");
        var logContent = card.find(".sl-log-content");
        var originalContent = logContent.data("original-content");
        
        if (!selectedTag) {
            // 필터 해제
            logContent.html(originalContent);
            card.find(".sl-filter-info").remove();
            card.find(".sl-tag").removeClass("sl-tag-active").css("background-color", "#007cba");
        } else {
            // 필터 적용
            var lines = originalContent.split("\n");
            var filteredLines = [];
            var inMatchingEntry = false;
            var entryCount = 0;
            
            for (var i = 0; i < lines.length; i++) {
                var line = lines[i];
                
                // 새로운 로그 항목의 시작을 확인
                if (line.match(/^\[\d{4}-\d{2}-\d{2}/)) {
                    inMatchingEntry = line.indexOf("[TAGS:") !== -1 && line.indexOf(selectedTag) !== -1;
                    if (inMatchingEntry) {
                        entryCount++;
                    }
                }
                
                if (inMatchingEntry) {
                    filteredLines.push(line);
                }
            }
            
            if (filteredLines.length > 0) {
                logContent.html(filteredLines.join("\n"));
                
                // 필터 정보 추가
                card.find(".sl-filter-info").remove();
                card.find(".sl-log-actions").after('<div class="sl-filter-info" style="background: #f0f0f0; padding: 8px 15px; margin: 10px 0; border-radius: 3px; display: flex; justify-content: space-between; align-items: center;"><span>필터링됨: <strong>' + selectedTag + '</strong> 태그 (' + entryCount + '개 항목)</span><a href="#" class="sl-clear-filter" style="color: #d63638;">필터 해제</a></div>');
            } else {
                logContent.html('<p style="color: #666; padding: 20px; text-align: center;">"' + selectedTag + '" 태그가 있는 로그가 없습니다.</p>');
                card.find(".sl-filter-info").remove();
            }
            
            // 태그 하이라이트
            card.find(".sl-tag").removeClass("sl-tag-active").css("background-color", "#007cba");
            card.find('.sl-tag[data-tag="' + selectedTag + '"]').addClass("sl-tag-active").css("background-color", "#d63638");
        }
    });
    
    // 태그 클릭 이벤트
    $(document).on("click", ".sl-tag", function() {
        var tag = $(this).data("tag");
        var card = $(this).closest(".sl-log-card");
        var filterSelect = card.find(".sl-tag-filter-select");
        
        if (filterSelect.length) {
            filterSelect.val(tag).trigger("change");
        }
    });
    
    // 필터 해제 링크
    $(document).on("click", ".sl-clear-filter", function(e) {
        e.preventDefault();
        var card = $(this).closest(".sl-log-card");
        card.find(".sl-tag-filter-select").val("").trigger("change");
    });
    
    // 날짜 변경 시 원본 컨텐츠 업데이트
    $(document).on("sl-content-updated", ".sl-log-card", function() {
        var logContent = $(this).find(".sl-log-content");
        logContent.data("original-content", logContent.html());
        
        // 태그 필터 드롭다운 업데이트
        var content = logContent.html();
        var availableTags = {};
        var matches = content.match(/\[TAGS: ([^\]]+)\]/g);
        
        if (matches) {
            matches.forEach(function(match) {
                var tagString = match.replace(/\[TAGS: /, "").replace(/\]/, "");
                var tags = tagString.split(", ");
                tags.forEach(function(tag) {
                    tag = tag.trim();
                    if (tag) {
                        availableTags[tag] = true;
                    }
                });
            });
        }
        
        var tagArray = Object.keys(availableTags).sort();
        var filterSelect = $(this).find(".sl-tag-filter-select");
        
        if (tagArray.length > 0 && filterSelect.length === 0) {
            // 필터 셀렉터 추가
            var filterHtml = '<div class="sl-tag-filter-selector"><select class="sl-tag-filter-select"><option value="">모든 태그 보기</option>';
            tagArray.forEach(function(tag) {
                filterHtml += '<option value="' + tag + '">' + tag + '</option>';
            });
            filterHtml += '</select></div>';
            $(this).find(".sl-log-date-selector").after(filterHtml);
        } else if (filterSelect.length > 0) {
            // 기존 셀렉터 업데이트
            var currentValue = filterSelect.val();
            filterSelect.empty().append('<option value="">모든 태그 보기</option>');
            tagArray.forEach(function(tag) {
                filterSelect.append('<option value="' + tag + '">' + tag + '</option>');
            });
            if (tagArray.indexOf(currentValue) !== -1) {
                filterSelect.val(currentValue);
            }
        }
    });

    // 로그 비우기
    $(document).on('click', '.sl-clear-log', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        var plugin = button.data('plugin');
        var date = button.data('date');
        
        card.addClass('sl-loading');
        
        $.ajax({
            url: sl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sl_clear_log',
                plugin: plugin,
                date: date,
                nonce: sl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // 파일 내용이 비워졌으므로 로그를 새로고침
                    card.find('.sl-log-content').html('<p>로그가 없습니다.</p>');
                    card.find('.sl-log-size').text('0 B');
                }
                card.removeClass('sl-loading');
            },
            error: function(xhr, status, error) {
                card.removeClass('sl-loading');
                alert('비우기에 실패했습니다.');
            }
        });
    });
    
    // 로그 파일 삭제
    $(document).on('click', '.sl-delete-file', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        var plugin = button.data('plugin');
        var date = button.data('date');
        
        card.addClass('sl-loading');
        
        $.ajax({
            url: sl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sl_delete_file',
                plugin: plugin,
                date: date,
                nonce: sl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // 파일이 삭제되었으므로 날짜 선택기에서 해당 날짜 제거
                    var option = card.find('.sl-log-date-select option[value="' + date + '"]');
                    option.remove();
                    
                    // 다른 날짜가 있으면 첫 번째 날짜로 자동 전환
                    var newDate = card.find('.sl-log-date-select option:first').val();
                    if (newDate) {
                        card.find('.sl-log-date-select').val(newDate).trigger('change');
                    } else {
                        // 모든 로그가 삭제되면 카드 제거
                        card.fadeOut(function() {
                            card.remove();
                        });
                    }
                } else {
                    card.removeClass('sl-loading');
                    alert('파일 삭제에 실패했습니다.');
                }
            },
            error: function(xhr, status, error) {
                card.removeClass('sl-loading');
                alert('파일 삭제에 실패했습니다.');
            }
        });
    });
    
    // 로그 복사
    $(document).on('click', '.sl-copy-log', function() {
        var button = $(this);
        var content = button.closest('.sl-log-card').find('.sl-log-content').text();
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(content).then(function() {
                var originalText = button.text();
                button.text('✓ 복사됨');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            });
        } else {
            // 폴백
            var textArea = $('<textarea>').val(content).css({
                position: 'fixed',
                left: '-999999px'
            }).appendTo('body');
            textArea[0].select();
            document.execCommand('copy');
            textArea.remove();
            
            var originalText = button.text();
            button.text('✓ 복사됨');
            setTimeout(function() {
                button.text(originalText);
            }, 2000);
        }
    });
    
    // 로그 새로고침
    $(document).on('click', '.sl-refresh-log', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        var plugin = button.data('plugin');
        var date = card.find('.sl-log-date-select').val();
        
        card.addClass('sl-loading');
        
        $.post(sl_ajax.ajax_url, {
            action: 'sl_refresh_log',
            plugin: plugin,
            date: date,
            nonce: sl_ajax.nonce
        }, function(response) {
            if (response.success) {
                card.find('.sl-log-content').html(response.data.content);
                card.find('.sl-log-size').text(response.data.size);
                card.trigger('sl-content-updated');
            }
            card.removeClass('sl-loading');
        });
    });
    
    // 날짜 변경
    $(document).on('change', '.sl-log-date-select', function() {
        var select = $(this);
        var card = select.closest('.sl-log-card');
        var plugin = card.find('.sl-refresh-log').data('plugin');
        var date = select.val();
        
        card.addClass('sl-loading');
        
        $.post(sl_ajax.ajax_url, {
            action: 'sl_refresh_log',
            plugin: plugin,
            date: date,
            nonce: sl_ajax.nonce
        }, function(response) {
            if (response.success) {
                card.find('.sl-log-content').html(response.data.content);
                card.find('.sl-log-size').text(response.data.size);
                
                // data-date 속성 업데이트
                card.find('.sl-clear-log, .sl-copy-log, .sl-refresh-log, .sl-delete-file').attr('data-date', date);
                card.trigger('sl-content-updated');
            }
            card.removeClass('sl-loading');
        });
    });
    
    // debug.log 비우기
    $(document).on('click', '.sl-clear-debug-log', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        
        card.addClass('sl-loading');
        
        $.ajax({
            url: sl_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sl_clear_debug_log',
                nonce: sl_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    card.find('.sl-debug-log-content').html('<p>debug.log 파일이 없습니다.</p>');
                    card.find('.sl-debug-log-size').text('0 B');
                } else {
                    alert(response.data || '비우기에 실패했습니다.');
                }
                card.removeClass('sl-loading');
            },
            error: function(xhr, status, error) {
                card.removeClass('sl-loading');
                alert('비우기에 실패했습니다.');
            }
        });
    });
    
    // debug.log 복사
    $(document).on('click', '.sl-copy-debug-log', function() {
        var button = $(this);
        var content = button.closest('.sl-log-card').find('.sl-debug-log-content').text();
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(content).then(function() {
                var originalText = button.text();
                button.text('✓ 복사됨');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            });
        } else {
            // 폴백
            var textArea = $('<textarea>').val(content).css({
                position: 'fixed',
                left: '-999999px'
            }).appendTo('body');
            textArea[0].select();
            document.execCommand('copy');
            textArea.remove();
            
            var originalText = button.text();
            button.text('✓ 복사됨');
            setTimeout(function() {
                button.text(originalText);
            }, 2000);
        }
    });
    
    // debug.log 새로고침
    $(document).on('click', '.sl-refresh-debug-log', function() {
        var button = $(this);
        var card = button.closest('.sl-log-card');
        
        card.addClass('sl-loading');
        
        $.post(sl_ajax.ajax_url, {
            action: 'sl_refresh_debug_log',
            nonce: sl_ajax.nonce
        }, function(response) {
            if (response.success) {
                card.find('.sl-debug-log-content').html(response.data.content);
                card.find('.sl-debug-log-size').text(response.data.size);
            }
            card.removeClass('sl-loading');
        });
    });
});