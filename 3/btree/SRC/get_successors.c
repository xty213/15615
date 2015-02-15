#include "def.h"

// external functions
extern int check_word(char *word);
extern int strtolow(char *s);
extern int FindInsertionPosition(struct KeyRecord * KeyListTraverser, char *Key, int *Found, NUMKEYS NumKeys, int Count);
extern struct PageHdr *FetchPage(PAGENO Page);
extern PAGENO treesearch_page(PAGENO PageNo, char *key);

int get_successors(char *key, int k, char *result[]) {
    if (check_word(key) == FALSE) {
        return 0;
    }
    strtolow(key);

    int cnt = 0; // found successors number
    int Count = 0, Found, i; // helper variables for FindInsertionPosition

    // find the first successor
    PAGENO page = treesearch_page(ROOT, key);
    struct PageHdr *PagePtr = FetchPage(page);
    struct KeyRecord *KeyListTraverser = PagePtr->KeyListPtr;
    int InsertionPosition = FindInsertionPosition(KeyListTraverser, key, &Found, PagePtr->NumKeys, Count);
    for (i = 0; i < InsertionPosition; i++)
            KeyListTraverser = KeyListTraverser->Next;
    if (KeyListTraverser == NULL) {
        page = PagePtr->PgNumOfNxtLfPg;
        if (page != NULLPAGENO) {
            PagePtr = FetchPage(page);
            KeyListTraverser = PagePtr->KeyListPtr;
        }
    }

    // keep finding successors
    while (cnt < k && KeyListTraverser != NULL) {
        // save the current successor
        result[cnt] = KeyListTraverser->StoredKey;
        // if the current page is over, read next logical page
        if (KeyListTraverser->Next == NULL) {
            page = PagePtr->PgNumOfNxtLfPg;
            // next logical page does not exist
            if (page == NULLPAGENO) {
                break;
            } else {
                PagePtr = FetchPage(page);
                KeyListTraverser = PagePtr->KeyListPtr;
            }
        } else {
            KeyListTraverser = KeyListTraverser->Next;
        }
        cnt++;
    }

    printf("found %d successors:\n", cnt);
    return cnt;
}

