package uz.edu.trainingcenter.navigation

object Routes {
    const val SPLASH = "splash"
    const val LOGIN = "login"
    const val REGISTER = "register"
    const val HOME = "home"
    const val SCHEDULE = "schedule"
    const val TESTS_LIST = "tests_list"
    const val TEST_TAKING = "test_taking/{testId}"
    const val TEST_RESULT = "test_result/{score}/{passed}"
    const val CERTIFICATES = "certificates"
    const val PROFILE = "profile"
    const val PROFESSIONS_LIST = "professions_list"
    const val PROFESSION_DETAIL = "profession_detail/{professionId}"
    const val NEWS_LIST = "news_list"
    const val MEDIA_LIST = "media_list"

    fun testTaking(testId: Int) = "test_taking/$testId"
    fun testResult(score: Int, passed: Boolean) = "test_result/$score/$passed"
    fun professionDetail(professionId: Int) = "profession_detail/$professionId"
}
