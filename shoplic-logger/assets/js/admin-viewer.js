jQuery(document).ready(function($) {
    // 원본 컨텐츠 저장
    $(".sl-log-content").each(function() {
        $(this).data("original-content", $(this).html());
    });
    
    // 필터 태그 버튼 클릭 이벤트
    $(document).on("click", ".sl-filter-tag-btn", function() {
        var button = $(this);
        var card = button.closest(".sl-log-card");
        
        // 토글 동작
        button.toggleClass("active");
        
        // 필터 적용
        applyMultipleFilters(card);
    });
    
    // 다중 필터 적용 함수
    function applyMultipleFilters(card) {
        var logContent = card.find(".sl-log-content");
        var originalContent = logContent.data("original-content");
        var activeButtons = card.find(".sl-filter-tag-btn.active");
        
        if (activeButtons.length === 0) {
            // 모든 필터 해제
            logContent.html(originalContent);
            card.find(".sl-filter-info").remove();
            card.find(".sl-tag").removeClass("sl-tag-active").css("background-color", "#007cba");
            return;
        }
        
        // 선택된 태그들 수집
        var selectedTags = [];
        activeButtons.each(function() {
            selectedTags.push($(this).data("tag"));
        });
        
        // 필터 모드 확인 (OR 또는 AND)
        var filterMode = card.find('input[name^="filter-mode"]:checked').val() || 'or';
        
        // 필터 적용
        var lines = originalContent.split("\n");
        var filteredLines = [];
        var inMatchingEntry = false;
        var entryCount = 0;
        
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];
            
            // 새로운 로그 항목의 시작을 확인
            if (line.match(/^\[\d{4}-\d{2}-\d{2}/)) {
                // HTML 태그를 포함한 상태에서 태그 검색
                var tagMatch = line.match(/data-tag="([^"]+)"/g);
                inMatchingEntry = false;
                
                if (tagMatch) {
                    var lineTags = [];
                    for (var j = 0; j < tagMatch.length; j++) {
                        var tag = tagMatch[j].replace(/data-tag="/, '').replace(/"/, '');
                        lineTags.push(tag);
                    }
                    
                    if (filterMode === 'or') {
                        // OR 연산: 선택된 태그 중 하나라도 있으면 표시
                        for (var k = 0; k < selectedTags.length; k++) {
                            if (lineTags.indexOf(selectedTags[k]) !== -1) {
                                inMatchingEntry = true;
                                entryCount++;
                                break;
                            }
                        }
                    } else {
                        // AND 연산: 선택된 태그가 모두 있어야 표시
                        var matchCount = 0;
                        for (var k = 0; k < selectedTags.length; k++) {
                            if (lineTags.indexOf(selectedTags[k]) !== -1) {
                                matchCount++;
                            }
                        }
                        if (matchCount === selectedTags.length) {
                            inMatchingEntry = true;
                            entryCount++;
                        }
                    }
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
            var filterText = selectedTags.join(filterMode === 'or' ? ' 또는 ' : ' 그리고 ');
            var modeText = filterMode === 'or' ? 'OR' : 'AND';
            card.find(".sl-log-actions").after('<div class="sl-filter-info" style="background: #f0f0f0; padding: 8px 15px; margin: 10px 0; border-radius: 3px; display: flex; justify-content: space-between; align-items: center;"><span>필터링됨 (' + modeText + '): <strong>' + filterText + '</strong> (' + entryCount + '개 항목)</span><a href="#" class="sl-clear-filter" style="color: #d63638;">모든 필터 해제</a></div>');
            
            // 선택된 태그들 하이라이트
            card.find(".sl-tag").removeClass("sl-tag-active").css("background-color", "#007cba");
            selectedTags.forEach(function(tag) {
                card.find('.sl-tag[data-tag="' + tag + '"]').addClass("sl-tag-active").css("background-color", "#d63638");
            });
        } else {
            logContent.html('<p style="color: #666; padding: 20px; text-align: center;">선택한 태그 조건에 맞는 로그가 없습니다.</p>');
            card.find(".sl-filter-info").remove();
        }
    }
    
    // 모든 필터 해제 버튼
    $(document).on("click", ".sl-filter-clear-all", function() {
        var card = $(this).closest(".sl-log-card");
        var logContent = card.find(".sl-log-content");
        var originalContent = logContent.data("original-content");
        
        // 모든 필터 버튼 비활성화
        card.find(".sl-filter-tag-btn").removeClass("active");
        
        // 원본 컨텐츠 복원
        logContent.html(originalContent);
        card.find(".sl-filter-info").remove();
        card.find(".sl-tag").removeClass("sl-tag-active").css("background-color", "#007cba");
    });
    
    // 태그 클릭 이벤트
    $(document).on("click", ".sl-tag", function() {
        var tag = $(this).data("tag");
        var card = $(this).closest(".sl-log-card");
        var filterButton = card.find('.sl-filter-tag-btn[data-tag="' + tag + '"]');
        
        if (filterButton.length) {
            filterButton.trigger("click");
        }
    });
    
    // 필터 해제 링크
    $(document).on("click", ".sl-clear-filter", function(e) {
        e.preventDefault();
        var card = $(this).closest(".sl-log-card");
        card.find(".sl-filter-clear-all").trigger("click");
    });
    
    // 필터 모드 변경 이벤트 (OR/AND)
    $(document).on("change", 'input[name^="filter-mode"]', function() {
        var card = $(this).closest(".sl-log-card");
        // 활성 필터가 있으면 다시 적용
        if (card.find(".sl-filter-tag-btn.active").length > 0) {
            applyMultipleFilters(card);
        }
    });
    
    // 날짜 변경 시 원본 컨텐츠 업데이트
    $(document).on("sl-content-updated", ".sl-log-card", function() {
        var logContent = $(this).find(".sl-log-content");
        logContent.data("original-content", logContent.html());
        
        // 태그 필터 드롭다운 업데이트
        var content = logContent.html();
        var availableTags = {};
        
        // HTML에서 data-tag 속성을 찾아서 태그 추출
        var tagMatches = content.match(/data-tag="([^"]+)"/g);
        if (tagMatches) {
            tagMatches.forEach(function(match) {
                var tag = match.replace(/data-tag="/, '').replace(/"/, '');
                if (tag) {
                    availableTags[tag] = true;
                }
            });
        }
        
        var tagArray = Object.keys(availableTags).sort();
        var filterWrapper = $(this).find(".sl-tag-filter-wrapper");
        var filterButtons = $(this).find(".sl-tag-filter-buttons");
        
        if (tagArray.length > 0 && filterWrapper.length === 0) {
            // 필터 UI 전체 추가
            var plugin = $(this).data("plugin");
            var filterHtml = '<div class="sl-tag-filter-wrapper">' +
                '<div class="sl-tag-filter-controls">' +
                '<button type="button" class="button button-small sl-filter-clear-all">모든 필터 해제</button>' +
                '<div class="sl-filter-mode">' +
                '<label><input type="radio" name="filter-mode-' + plugin + '" value="or" checked><span>OR (하나라도)</span></label>' +
                '<label><input type="radio" name="filter-mode-' + plugin + '" value="and"><span>AND (모두)</span></label>' +
                '</div></div>' +
                '<div class="sl-tag-filter-buttons">';
            tagArray.forEach(function(tag) {
                filterHtml += '<button type="button" class="button button-small sl-filter-tag-btn" data-tag="' + tag + '">' + tag + '</button>';
            });
            filterHtml += '</div></div>';
            $(this).find(".sl-log-date-selector").after(filterHtml);
        } else if (filterButtons.length > 0) {
            // 기존 버튼만 업데이트
            var activeTags = [];
            filterButtons.find(".sl-filter-tag-btn.active").each(function() {
                activeTags.push($(this).data("tag"));
            });
            filterButtons.find(".sl-filter-tag-btn").remove();
            
            tagArray.forEach(function(tag) {
                var button = $('<button type="button" class="button button-small sl-filter-tag-btn" data-tag="' + tag + '">' + tag + '</button>');
                if (activeTags.indexOf(tag) !== -1) {
                    button.addClass("active");
                }
                filterButtons.append(button);
            });
            
            // 활성 필터가 있으면 다시 적용
            if (activeTags.length > 0) {
                applyMultipleFilters($(this));
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
                alert('전체 비우기에 실패했습니다.');
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
                alert('전체 비우기에 실패했습니다.');
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