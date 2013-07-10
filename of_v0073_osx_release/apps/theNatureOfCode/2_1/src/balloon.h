//
//  balloon.h
//  2_1
//
//  Created by Tobias Treppmann on 6/11/13.
//
//

#ifndef ____1__balloon__
#define ____1__balloon__

#include <iostream>
#include "ofMain.h"

class Balloon {
    
public:
    Balloon();
    void update();
    void display();
    void checkEdges();

    ofVec2f location;
    ofVec2f velocity;
    ofVec2f acceleration;
    float noiseX, noiseY;
    ofVec2f target;
    ofVec2f dir;
    ofVec2f wind;
    float topspeed = 10.0;
};



#endif /* defined(____1__balloon__) */
