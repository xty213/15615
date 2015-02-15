#include "def.h"


int get_successors(char *key, int k, char *result[]) {
    int cnt = 0;

    // try to find successors
    for (; cnt < k; cnt++) {
        result[cnt] = "alexander";
    }

    printf("found %d successors:\n", cnt);
    return cnt;
}

