//
//  mover.h
//  natureofcode_1_ex_1_7
//
//  Created by Tobias Treppmann on 5/31/13.
//
//

#ifndef __natureofcode_1_ex_1_7__mover__
#define __natureofcode_1_ex_1_7__mover__

#include <iostream>
#include "ofMain.h"

class Mover {
    
public:
    Mover();
    void update();
    void display();
    void checkEdges();
    void accelerate();
    void slowdown();
    
    ofVec2f location;
    ofVec2f velocity;
    ofVec2f acceleration;
    float topspeed;
    float noiseX;
    float noiseY;
};




#endif /* defined(__natureofcode_1_ex_1_7__mover__) */
