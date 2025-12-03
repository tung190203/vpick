<template>
  <div class="min-h-screen bg-gray-50 flex animate-fadeIn">

    <aside
      class="w-72 bg-white shadow-xl border-r border-gray-100 p-6 h-screen sticky top-0 overflow-y-auto flex flex-col justify-between">
      <div>
        <h2 class="text-xl font-bold text-primary mb-6">Danh mục</h2>

        <div class="space-y-3">
          <div>
            <button @click="toggleMainTab('terms')" :class="[
              'w-full text-left px-4 py-3 rounded-lg font-semibold transition flex justify-between items-center',
              activeMainTab === 'terms'
                ? 'bg-primary text-white shadow'
                : 'text-gray-700 hover:bg-red-100'
            ]">
              <span>Điều khoản sử dụng</span>

              <svg :class="[
                'w-4 h-4 transition',
                activeMainTab === 'terms' ? 'rotate-180' : 'rotate-0'
              ]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M6 9l6 6 6-6" />
              </svg>
            </button>

            <transition name="fade">
              <div v-if="activeMainTab === 'terms'" class="ml-4 mt-2 space-y-1 border-l border-gray-200 pl-3">
                <button @click="scrollTo('introduce')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'introduce'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Giới thiệu
                </button>
                <button @click="scrollTo('scope')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'scope'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Phạm vi áp dụng
                </button>
                <button @click="scrollTo('general')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'general'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Quy định chung
                </button>
                <button @click="scrollTo('limitation')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'limitation'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Giới hạn trách nhiệm
                </button>
                <button @click="scrollTo('violations')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'violations'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Chấm dứt và xử lý vi phạm
                </button>
                <button @click="scrollTo('security')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'security'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Bảo mật và quyền riêng tư
                </button>
                <button @click="scrollTo('dispute')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'dispute'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Giải quyết tranh chấp
                </button>
                <button @click="scrollTo('regulations')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'regulations'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Sửa đổi Quy chế
                </button>
                <button @click="scrollTo('information')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'information'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Thông tin liên hệ
                </button>

              </div>
            </transition>

          </div>
          <div>
            <button @click="toggleMainTab('privacy')" :class="[
              'w-full text-left px-4 py-3 rounded-lg font-semibold transition flex justify-between items-center',
              activeMainTab === 'privacy'
                ? 'bg-primary text-white shadow'
                : 'text-gray-700 hover:bg-red-100'
            ]">
              <span>Chính sách bảo mật</span>

              <svg :class="[
                'w-4 h-4 transition',
                activeMainTab === 'privacy' ? 'rotate-180' : 'rotate-0'
              ]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M6 9l6 6 6-6" />
              </svg>
            </button>

            <transition name="fade">
              <div v-if="activeMainTab === 'privacy'" class="ml-4 mt-2 space-y-1 border-l border-gray-200 pl-3">
                <button @click="scrollTo('p-intro')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'p-intro'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Giới thiệu
                </button>
                <button @click="scrollTo('p-collect')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'p-collect'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Dữ liệu chúng tôi thu thập
                </button>
                <button @click="scrollTo('p-purpose')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'p-purpose'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Mục đích sử dụng dữ liệu
                </button>
                <button @click="scrollTo('p-share')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'p-share'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Chia sẻ dữ liệu với bên thứ ba
                </button>
                <button @click="scrollTo('p-security')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'p-security'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Lưu trữ & bảo mật dữ liệu
                </button>
                <button @click="scrollTo('p-user')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'p-user'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Quyền của người dùng
                </button>
                <button @click="scrollTo('p-storage')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'p-storage'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Thời gian lưu trữ
                </button>
                <button @click="scrollTo('p-policy')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'p-policy'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Thay đổi chính sách
                </button>
                <button @click="scrollTo('p-contact')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'p-contact'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Thông tin liên hệ
                </button>
              </div>
            </transition>

          </div>
          <div>
            <button @click="toggleMainTab('cookie')" :class="[
              'w-full text-left px-4 py-3 rounded-lg font-semibold transition flex justify-between items-center',
              activeMainTab === 'cookie'
                ? 'bg-primary text-white shadow'
                : 'text-gray-700 hover:bg-red-100'
            ]">
              <span>Chính sách Cookie</span>

              <svg :class="[
                'w-4 h-4 transition',
                activeMainTab === 'cookie' ? 'rotate-180' : 'rotate-0'
              ]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M6 9l6 6 6-6" />
              </svg>
            </button>

            <transition name="fade">
              <div v-if="activeMainTab === 'cookie'" class="ml-4 mt-2 space-y-1 border-l border-gray-200 pl-3">
                <button @click="scrollTo('c-what')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'c-what'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Cookie là gì?
                </button>
                <button @click="scrollTo('c-use')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'c-use'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Mục đích sử dụng Cookie
                </button>
                <button @click="scrollTo('c-data')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'c-data'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Dữ liệu được thu thập thông qua Cookie
                </button>
                <button @click="scrollTo('c-third')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'c-third'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Bên thứ ba có sử dụng Cookie không?
                </button>
                <button @click="scrollTo('c-storage')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'c-storage'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Thời hạn lưu trữ cookie
                </button>
                <button @click="scrollTo('c-manage')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'c-manage'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Cách quản lý hoặc vô hiệu hóa Cookie
                </button>
                <button @click="scrollTo('c-change')" :class="[
                  'block w-full text-left px-3 py-2 rounded-md text-sm transition',
                  activeSub === 'c-change'
                    ? 'bg-secondary text-white'
                    : 'text-gray-700 hover:bg-red-100'
                ]">
                  Thay đổi Chính sách Cookie
                </button>
              </div>
            </transition>

          </div>
        </div>
      </div>

      <div class="mt-8 pt-4 border-t border-gray-200">
        <router-link to="/"
          class="flex items-center space-x-3 px-4 py-3 rounded-lg font-semibold transition text-gray-700 hover:bg-red-100">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
            stroke-linecap="round" stroke-linejoin="round">
            <path
              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0v-4a1 1 0 011-1h2a1 1 0 011 1v4" />
          </svg>
          <span>Quay lại Trang chủ</span>
        </router-link>
      </div>
    </aside>

    <main class="flex-1 p-10">
      <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-md p-8 border border-gray-100">

        <template v-if="activeMainTab === 'terms'">
          <h1 class="text-3xl font-bold text-primary mb-8">
            Điều khoản sử dụng
          </h1>

          <section id="introduce" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              I. Giới thiệu
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm mb-4">
              Quy chế sử dụng dịch vụ này (“Quy chế”) quy định các điều khoản, quyền và nghĩa vụ của khách hàng khi sử
              dụng dịch vụ do <strong>Công ty TNHH Dịch vụ Phần mềm VRPLUS</strong> (sau đây gọi là “Công ty”, “chúng
              tôi”) cung cấp.
            </p>
            <p class="text-gray-600 leading-relaxed text-sm">
              Khi truy cập hoặc sử dụng dịch vụ, người dùng (“Bạn”) được xem là đã đồng ý với toàn bộ Quy chế này.
            </p>

          </section>

          <section id="scope" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              II. Phạm vi áp dụng
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm">
              Quy chế này áp dụng cho tất cả người dùng, khách hàng và đối tác sử dụng dịch vụ thông qua website, ứng
              dụng di động hoặc các nền tảng trực tuyến khác do Công ty vận hành.
            </p>
          </section>

          <section id="general" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              III. Quy định chung
            </h2>
            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">1. Tài khoản và bảo mật </h3>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Người dùng cần đăng ký tài khoản hợp lệ để sử dụng các tính năng yêu cầu xác thực.</li>
              <li>Thông tin tài khoản phải chính xác, đầy đủ và được cập nhật khi có thay đổi.</li>
              <li>Người dùng chịu trách nhiệm bảo mật tài khoản và không chia sẻ thông tin đăng nhập cho bên thứ ba.
              </li>
              <li>Mọi thiệt hại phát sinh từ việc quản lý tài khoản không an toàn sẽ do người dùng tự chịu.</li>
            </ul>
            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">2. Sử dụng dịch vụ </h3>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Dịch vụ chỉ được sử dụng cho mục đích hợp pháp, phù hợp quy định pháp luật và đạo đức xã hội.</li>
              <li>Người dùng không được phép đăng tải hoặc phát tán nội dung xấu, giả mạo, lừa đảo hoặc gây gián đoạn hệ
                thống.</li>
              <li>Mọi hành vi gian lận, xâm nhập trái phép hoặc lạm dụng dịch vụ sẽ bị xử lý theo Quy chế và pháp luật.
              </li>
            </ul>
            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">3. Quyền và nghĩa vụ của người dùng </h3>
            <p class="font-medium mt-3">Người dùng có quyền:</p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Truy cập và sử dụng các tính năng, dịch vụ theo phạm vi mà mình đã đăng ký</li>
              <li>Nhận sự hỗ trợ kỹ thuật hoặc giải đáp khi cần</li>
            </ul>
            <p class="font-medium mt-3">Người dùng có nghĩa vụ:</p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Chịu trách nhiệm đối với nội dung do mình cung cấp.</li>
              <li>Đảm bảo nội dung không vi phạm quyền sở hữu trí tuệ của bất kỳ bên thứ ba nào.</li>
              <li>Tuân thủ hướng dẫn sử dụng và không can thiệp trái phép vào hệ thống.</li>
            </ul>
            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">4. Quyền và nghĩa vụ của công ty </h3>
            <p class="font-medium mt-3">Công ty có quyền:</p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Tạm ngưng hoặc chấm dứt cung cấp dịch vụ đối với tài khoản có dấu hiệu vi phạm</li>
              <li>Điều chỉnh, nâng cấp hoặc thay đổi dịch vụ khi cần thiết.</li>
            </ul>
            <p class="font-medium mt-3">Công ty có trách nhiệm:</p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Cung cấp dịch vụ theo cam kết và đảm bảo an toàn thông tin.</li>
              <li>Bảo mật dữ liệu người dùng theo quy định pháp luật và chính sách riêng tư hiện hành.</li>
            </ul>
          </section>
          <section id="limitation" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              IV. Giới hạn trách nhiệm
            </h2>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Công ty không chịu trách nhiệm cho các thiệt hại phát sinh từ lỗi phía người dùng hoặc sự kiện bất khả
                kháng (thiên tai, sự cố kỹ thuật, tấn công mạng, v.v.).</li>
              <li>Dịch vụ được cung cấp theo nguyên tắc “như hiện trạng” và Công ty không cam kết hoạt động liên tục
                100%.</li>
            </ul>
          </section>
          <section id="violations" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              V. Chấm dứt xử lý và vi phạm
            </h2>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Công ty có quyền tạm ngưng hoặc chấm dứt quyền truy cập nếu người dùng vi phạm Quy chế.</li>
              <li>Hành vi vi phạm nghiêm trọng có thể dẫn đến khóa tài khoản, hủy dịch vụ và yêu cầu bồi thường thiệt
                hại (nếu có).</li>
            </ul>
          </section>
          <section id="security" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              VI. Bảo mật và quyền riêng tư
            </h2>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Thông tin cá nhân của người dùng sẽ được thu thập, sử dụng và bảo vệ theo Chính sách bảo mật của Công
                ty.</li>
              <li>Thông tin người dùng sẽ không được chia sẻ cho bên thứ ba nếu không có sự đồng ý, trừ trường hợp pháp
                luật yêu cầu.</li>
            </ul>
          </section>
          <section id="dispute" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              VII. Giải quyết tranh chấp
            </h2>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Tranh chấp phát sinh sẽ được ưu tiên giải quyết thông qua thương lượng và hòa giải.</li>
              <li>Nếu không đạt được thỏa thuận, tranh chấp sẽ được giải quyết theo pháp luật Việt Nam.</li>
              <li>Tòa án nơi Công ty đặt trụ sở sẽ là cơ quan xét xử có thẩm quyền cuối cùng.</li>
            </ul>
          </section>
          <section id="regulations" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              VIII. Sửa đổi Quy chế
            </h2>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Công ty có quyền sửa đổi, bổ sung Quy chế để phù hợp với tình hình thực tế.</li>
              <li>Các thay đổi sẽ được thông báo qua website, ứng dụng hoặc email.</li>
            </ul>
          </section>
          <section id="information" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              IX. Thông tin liên hệ
            </h2>
            <h6 class="text-base font-semibold text-gray-800 mb-2 mt-4">Mọi thắc mắc hoặc yêu cầu hỗ trợ, vui lòng liên
              hệ: </h6>
            <p class="text-sm font-semibold text-gray-600 mb-2">Công ty TNHH Dịch vụ Phần mềm VRPLUS</p>
            <ul class="text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>📍 <strong>Địa chỉ:</strong> Số 27, ngõ 41 Phùng Chí Kiên, Phường Nghĩa Đô, Thành phố Hà Nội</li>
              <li>📧 <strong>Email:</strong> hello@vrplus.vn</li>
              <li>📞 <strong>Hotline:</strong> (+84) 963.186.388</li>
            </ul>
          </section>
        </template>

        <template v-if="activeMainTab === 'privacy'">
          <h1 class="text-3xl font-bold text-primary mb-8">
            Chính sách bảo mật
          </h1>

          <section id="p-intro" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              I. Giới thiệu
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm mb-4">
              Ứng dụng Picki ("Ứng dụng") được vận hành bởi Công ty TNHH Dịch vụ Phần mềm VRPLUS. Chính sách này mô tả
              cách chúng tôi thu thập, sử dụng và bảo vệ dữ liệu cá nhân của người dùng.
            </p>
          </section>

          <section id="p-collect" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              II. Dữ liệu chúng tôi thu thập
            </h2>

            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">2.1 Dữ liệu vị trí (Location)</h3>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Ứng dụng có thể truy cập vị trí gần đúng hoặc chính xác của bạn để:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Gợi ý sân chơi hoặc đối thủ gần bạn</li>
              <li>Hiển thị bản đồ và các tính năng định vị</li>
              <li>Tối ưu hóa trải nghiệm người dùng</li>
            </ul>
            <p class="text-gray-600 leading-relaxed text-sm mt-2">
              ✓ Chúng tôi <strong>không bán</strong>, lưu trữ hoặc chia sẻ dữ liệu vị trí cho bên thứ ba không được ủy
              quyền.
            </p>

            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">2.2 Camera & Ảnh / Media</h3>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Ứng dụng có thể truy cập:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Camera: chụp avatar, quét mã QR</li>
              <li>Thư viện ảnh: tải ảnh đại diện</li>
            </ul>
            <p class="text-gray-600 leading-relaxed text-sm mt-2">
              Quyền truy cập chỉ hoạt động khi bạn cho phép.
            </p>

            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">2.3 Dữ liệu tài khoản</h3>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Chúng tôi có thể thu thập:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Email</li>
              <li>Họ tên</li>
              <li>Số điện thoại</li>
              <li>Hồ sơ vận động viên & thành tích thể thao</li>
            </ul>

            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">2.4 Dữ liệu thiết bị & phân tích (Analytics)</h3>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Bao gồm:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Loại thiết bị</li>
              <li>Phiên bản hệ điều hành</li>
              <li>Firebase Analytics</li>
              <li>ID quảng cáo (nếu được sử dụng)</li>
            </ul>
          </section>

          <section id="p-purpose" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              III. Mục đích sử dụng dữ liệu
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Thông tin cá nhân chỉ được dùng để:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Cung cấp đúng dịch vụ người dùng yêu cầu</li>
              <li>Bảo mật tài khoản và kiểm soát gian lận</li>
              <li>Gửi thông báo quan trọng về ứng dụng</li>
              <li>Cải thiện trải nghiệm người dùng</li>
              <li>Phát triển tính năng mới dựa trên phân tích dữ liệu</li>
              <li>Tuân thủ yêu cầu từ cơ quan nhà nước khi có văn bản hợp lệ</li>
            </ul>
          </section>

          <section id="p-share" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              IV. Chia sẻ dữ liệu với bên thứ ba
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Chúng tôi không bán, không cho thuê, không trao đổi thông tin cá nhân. Chúng tôi chỉ chia sẻ dữ liệu khi:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Có sự đồng ý rõ ràng của người dùng</li>
              <li>Đối tác kỹ thuật tham gia vận hành hệ thống (hosting, email service, Firebase, analytic tool) – nhưng
                chỉ trong phạm vi tối thiểu để thực hiện dịch vụ</li>
              <li>Cơ quan có thẩm quyền yêu cầu theo đúng quy định pháp luật</li>
            </ul>
            <p class="text-gray-600 leading-relaxed text-sm mt-2">
              Tất cả bên thứ ba đều phải tuân thủ cam kết bảo mật của chúng tôi.
            </p>
          </section>

          <section id="p-security" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              V. Lưu trữ & bảo mật dữ liệu
            </h2>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Dữ liệu được lưu trữ tại máy chủ đạt chuẩn bảo mật quốc tế</li>
              <li>Mật khẩu được mã hóa; thông tin nhạy cảm được xử lý bằng các giao thức bảo mật (SSL/TLS)</li>
              <li>Hệ thống có cơ chế sao lưu và phục hồi dữ liệu định kỳ</li>
              <li>Chúng tôi thường xuyên rà soát lỗ hổng, cập nhật bản vá và triển khai biện pháp phòng chống tấn công
              </li>
            </ul>
          </section>

          <section id="p-user" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              VI. Quyền của người dùng
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Người dùng có quyền:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Yêu cầu xem, chỉnh sửa hoặc cập nhật thông tin cá nhân</li>
              <li>Yêu cầu xóa tài khoản và dữ liệu liên quan (trừ dữ liệu cần lưu theo pháp luật)</li>
              <li>Thu hồi quyền truy cập Camera, Vị trí, Thư viện ảnh bất cứ lúc nào trong cài đặt thiết bị</li>
              <li>Từ chối nhận thông báo marketing</li>
              <li>Khiếu nại nếu thấy thông tin bị thu thập hoặc sử dụng sai mục đích</li>
            </ul>
          </section>

          <section id="p-storage" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              VII. Thời gian lưu trữ
            </h2>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Dữ liệu được lưu trong suốt thời gian tài khoản hoạt động</li>
              <li>Sau khi xóa tài khoản, dữ liệu sẽ được xử lý xóa trong vòng 30 ngày, trừ trường hợp cần giữ lại theo
                luật định</li>
            </ul>
          </section>

          <section id="p-policy" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              VIII. Thay đổi chính sách
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm">
              Khi có thay đổi, chúng tôi sẽ cập nhật tại trang này và/hoặc gửi thông báo qua email hoặc ứng dụng. Việc
              tiếp tục sử dụng dịch vụ đồng nghĩa với việc đồng ý các thay đổi mới.
            </p>
          </section>

          <section id="p-contact" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              IX. Thông tin liên hệ
            </h2>
            <h6 class="text-base font-semibold text-gray-800 mb-2 mt-4">Mọi thắc mắc về chính sách bảo mật, vui lòng
              liên hệ:</h6>
            <p class="text-sm font-semibold text-gray-600 mb-2">Công ty TNHH Dịch vụ Phần mềm VRPLUS</p>
            <ul class="text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>📍 <strong>Địa chỉ:</strong> Số 27, ngõ 41 Phùng Chí Kiên, Phường Nghĩa Đô, Thành phố Hà Nội</li>
              <li>📧 <strong>Email:</strong> hello@vrplus.vn</li>
              <li>📞 <strong>Hotline:</strong> (+84) 963.186.388</li>
            </ul>
          </section>
        </template>

        <template v-if="activeMainTab === 'cookie'">
          <h1 class="text-3xl font-bold text-primary mb-8">
            Chính sách Cookie
          </h1>

          <section id="c-what" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              I. Cookie là gì?
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm mb-4">
              Cookie là các tệp nhỏ được lưu trên thiết bị (điện thoại, máy tính, tablet) khi người dùng truy cập ứng
              dụng hoặc website Picki. Cookie giúp nhận diện người dùng, lưu cài đặt và tối ưu trải nghiệm sử dụng.
            </p>
            <p class="text-gray-600 leading-relaxed text-sm">
              <strong>Nói đơn giản:</strong> Cookie giúp Picki nhớ bạn là ai và để app chạy mượt hơn.
            </p>
          </section>

          <section id="c-use" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              II. Mục đích sử dụng cookie
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm">
              Picki sử dụng Cookie cho các mục đích sau:
            </p>
            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">1. Cookie cần thiết (Strictly Necessary Cookies)
            </h3>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Giúp ứng dụng hoạt động đúng chức năng:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Duy trì đăng nhập</li>
              <li>Ghi nhớ phiên làm việc</li>
              <li>Bảo mật khi xác thực người dùng. Nếu tắt nhóm cookie này, ứng dụng có thể không hoạt động ổn định.
              </li>
            </ul>
            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">2. Cookie hiệu suất (Performance Cookies) </h3>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Giúp Picki hiểu cách người dùng tương tác, từ đó tối ưu trải nghiệm:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Đo tốc độ tải</li>
              <li>Theo dõi lỗi hoặc crash</li>
              <li>Đánh giá hiệu suất tính năng</li>
            </ul>
            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">3. Cookie chức năng (Functional Cookies)</h3>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Ghi nhớ lựa chọn của bạn:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Ngôn ngữ</li>
              <li>Cài đặt cá nhân hoá</li>
              <li>Tùy chọn hiển thị. Nhờ đó, mỗi lần mở app lại không phải thiết lập từ đầu</li>
            </ul>
            <h3 class="text-xl font-semibold text-gray-800 mb-2 mt-4">4. Cookie phân tích & marketing (Analytics &
              Marketing Cookies)</h3>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Chỉ được bật khi người dùng đồng ý. Dùng để:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Đo hành vi người dùng tổng quan (không nhận dạng cá nhân)</li>
              <li>Tối ưu chiến dịch truyền thông</li>
              <li>Cá nhân hóa nội dung hiển thị</li>
            </ul>
          </section>

          <section id="c-data" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              III. Dữ liệu được thu thập thông qua Cookie
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Tùy loại cookie, dữ liệu có thể bao gồm:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>ID thiết bị</li>
              <li>Lịch sử tương tác trong app</li>
              <li>Trang/lượt click</li>
              <li>Địa chỉ IP rút gọn (không đủ để định danh cá nhân)</li>
              <li>Thông tin cấu hình thiết bị</li>
            </ul>
            <p class="text-gray-600 leading-relaxed text-sm mt-2">
              Không thu thập thông tin nhạy cảm như mật khẩu, dữ liệu thanh toán, danh bạ, tin nhắn,…
            </p>
          </section>
          <section id="c-third" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              IV. Bên thứ ba có sử dụng Cookie không?
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Picki có thể tích hợp một số công cụ phân tích từ bên thứ ba như:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Firebase Analytics</li>
              <li>Google Analytics for Apps</li>
              <li>Meta SDK (nếu chạy quảng cáo)</li>
            </ul>
            <p class="text-gray-600 leading-relaxed text-sm mt-2">
              Các bên này chỉ thu thập dữ liệu theo đúng chức năng đo lường và không có quyền truy cập dữ liệu cá nhân
              nhạy cảm của người dùng.
            </p>
          </section>
          <section id="c-storage" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              V. Thời hạn lưu trữ cookie
            </h2>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Cookie phiên (session cookie): tự xóa sau khi người dùng thoát app</li>
              <li>Cookie dài hạn (persistent cookie): lưu tối đa 12–24 tháng, tuỳ loại.</li>
            </ul>
          </section>
          <section id="c-manage" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              VI. Cách quản lý hoặc vô hiệu hóa Cookie
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm mb-2">
              Người dùng có thể:
            </p>
            <ul class="list-disc list-inside text-gray-600 leading-relaxed text-sm ml-4 space-y-1">
              <li>Tắt hoặc xóa cookie trong phần cài đặt trình duyệt/thiết bị</li>
              <li>Tắt cookie phân tích hoặc marketing trong mục Cài đặt quyền riêng tư của ứng dụng.</li>
            </ul>
            <p class="text-gray-600 leading-relaxed text-sm mt-2">
              Lưu ý: Việc tắt một số cookie có thể khiến ứng dụng hoạt động không ổn định.
            </p>
          </section>
          <section id="c-change" class="mb-12 scroll-mt-24">
            <h2 class="text-2xl font-semibold text-primary mb-4 flex items-center">
              VII. Thay đổi Chính sách Cookie
            </h2>
            <p class="text-gray-600 leading-relaxed text-sm">
              Chính sách cookie có thể được cập nhật theo nhu cầu vận hành hoặc theo quy định pháp luật. Mọi thay đổi sẽ
              được thông báo rõ ràng trên website hoặc ứng dụng.
            </p>
          </section>
        </template>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from "vue";
const activeMainTab = ref("terms"); // Khởi tạo tab mặc định
const activeSub = ref(""); // Khởi tạo sub-tab hoạt động

function toggleMainTab(id) {
  // Logic đóng/mở tab
  activeMainTab.value = activeMainTab.value === id ? null : id;
  activeSub.value = ""; // Reset sub-tab khi chuyển tab chính
}

function scrollTo(id) {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: "smooth" });
    activeSub.value = id; // Đặt sub-tab hoạt động
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(15px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn .4s ease-out;
}
</style>