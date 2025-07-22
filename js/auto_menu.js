$(document).ready(function () {
    var indexHtml = '<ul class="nav">';
    $("h1").each(function () {
        var h1Id = $(this).attr("id");
        if (h1Id) {
            indexHtml += '<li><a href="#' + h1Id + '"><b>' + $(this).text() + '</b></a>';
            indexHtml += '<ul class="nav h2item">';
            $("h2[id^='" + h1Id + "_']").each(function () {
                var h2Id = $(this).attr("id");
                if (h2Id) {
                    indexHtml += '<li><a href="#' + h2Id + '">' + $(this).text() + '</a>';
                    indexHtml += '<ul class="nav h3item">';
                    $("h3[id^='" + h2Id + "_']").each(function () {
                        var h3Id = $(this).attr("id");
                        if (h3Id) {
                            indexHtml += '<li><a href="#' + h3Id + '">' + $(this).text() + '</a></li>';
                        }
                    });
                    indexHtml += '</ul></li>';
                }
            });
            indexHtml += '</ul></li>';
        }
    });
    indexHtml += '</ul>';
    $('.menu-items').html(indexHtml);

    // Scrollspy activeren
    $('body')
        .scrollspy({ target: '.menu-items' })
        .on('activate.bs.scrollspy', function () {
            $('.h2item').hide();
            $('.h3item').hide();

            var h2active = $('.h2item > .active');
            h2active.parent().show();
            h2active.find('ul').show();
            $('.active > .h2item').show(300);
        });

    // Scroll fix bij directe URL-navigatie
    if (window.location.hash) {
        var target = $(window.location.hash);
        if (target.length) {
            setTimeout(function () {
                // Houd rekening met vaste headers (pas offset aan indien nodig)
                var offset = target.offset().top - 20;
                $('html, body').scrollTop(offset);
            }, 300); // Wacht tot DOM en menu klaar zijn
        }
    }
});
